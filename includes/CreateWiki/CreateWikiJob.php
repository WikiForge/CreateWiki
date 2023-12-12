<?php

namespace WikiForge\CreateWiki\CreateWiki;

use Exception;
use ExtensionRegistry;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use User;
use WikiForge\CreateWiki\RequestWiki\WikiRequest;
use WikiForge\CreateWiki\WikiManager;
use WikiForge\ManageWiki\Helpers\ManageWikiSettings;

class CreateWikiJob extends Job {
	public function __construct( Title $title, array $params ) {
		parent::__construct( 'CreateWikiJob', $params );
	}

	public function run() {
		$hookRunner = MediaWikiServices::getInstance()->get( 'CreateWikiHookRunner' );
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CreateWiki' );
		$wm = new WikiManager( $this->params['dbname'], $hookRunner );
		$wr = new WikiRequest( $this->params['id'], $hookRunner );

		$notValid = $wm->checkDatabaseName( $this->params['dbname'] );

		if ( $notValid ) {
			$wr->addComment( $notValid, User::newSystemUser( 'CreateWiki Extension' ) );

			return true;
		}

		try {
			$wm->create(
				$this->params['sitename'],
				$this->params['language'],
				$this->params['private'],
				$this->params['category'],
				$this->params['requester'],
				$this->params['creator'],
				"[[Special:RequestWikiQueue/{$this->params['id']}|Requested]]"
			);

			if (
				ExtensionRegistry::getInstance()->isLoaded( 'ManageWiki' ) &&
				ExtensionRegistry::getInstance()->isLoaded( 'WikiDiscover' ) &&
				$config->get( 'WikiDiscoverUseDescriptions' ) &&
				$config->get( 'RequestWikiUseDescriptions' ) &&
				isset( $this->params['description'] )
			) {
				$mwSettings = new ManageWikiSettings( $this->params['dbname'] );

				$description = $mwSettings->list()['wgWikiDiscoverDescription'] ?? '';

				if ( !$notValid && $this->params['description'] != $description ) {
					$mwSettings->modify( [ 'wgWikiDiscoverDescription' => $this->params['description'] ] );

					$mwSettings->commit();
				}
			}
		} catch ( Exception $e ) {
			$wr->addComment( 'Exception experienced creating the wiki. Error is: ' . $e->getMessage(), User::newSystemUser( 'CreateWiki Extension' ) );
			$wr->reopen( User::newSystemUser( 'CreateWiki Extension' ), false );

			return true;
		}

		$wr->addComment( 'Wiki created.', User::newSystemUser( 'CreateWiki Extension' ) );

		return true;
	}
}

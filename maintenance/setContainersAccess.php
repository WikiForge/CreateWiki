<?php

namespace WikiForge\CreateWiki\Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

use FileBackend;
use Maintenance;
use MediaWiki\MediaWikiServices;
use WikiForge\CreateWiki\RemoteWiki;

class SetContainersAccess extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Secure containers for wiki if $wgCreateWikiUseSecureContainers is enabled,' .
			' or makes them public if not. Always secures deleted and temp containers. Also creates containers that don\'t exist.' );

		$this->requireExtension( 'CreateWiki' );
	}

	public function execute() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CreateWiki' );
		$repo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();

		$backend = $repo->getBackend();

		$wiki = new RemoteWiki( $config->get( 'DBname' ) );
		$isPrivate = $wiki->isPrivate();

		foreach ( [ 'public', 'thumb', 'transcoded', 'temp', 'deleted' ] as $zone ) {
			$dir = $repo->getZonePath( $zone );
			$secure = ( $config->get( 'CreateWikiUseSecureContainers' ) &&
				( $zone === 'deleted' || $zone === 'temp' || $isPrivate )
			) ? [ 'noAccess' => true, 'noListing' => true ] : [];

			$this->prepareDirectory( $backend, $dir, $secure );
		}

		if ( $config->get( 'CreateWikiUseSecureContainers' ) && $config->get( 'CreateWikiExtraSecuredContainers' ) ) {
			foreach ( $config->get( 'CreateWikiExtraSecuredContainers' ) as $container ) {
				$dir = $backend->getContainerStoragePath( $container );

				$secure = $isPrivate ? [ 'noAccess' => true, 'noListing' => true ] : [];

				if ( $isPrivate || $backend->directoryExists( [ 'dir' => $dir ] ) ) {
					$this->prepareDirectory( $backend, $dir, $secure );
				}
			}
		}
	}

	protected function prepareDirectory( FileBackend $backend, $dir, array $secure ) {
		// Create zone if it doesn't exist...
		$this->output( "Making sure '$dir' exists..." );
		$status = $backend->prepare( [ 'dir' => $dir ] + $secure );

		if ( !$status->isOK() ) {
			$this->output( 'failed...' );
		}

		// Make sure zone has the right ACLs...
		if ( $secure ) {
			// private
			$this->output( 'making private...' );
			$status->merge( $backend->secure( [ 'dir' => $dir ] + $secure ) );
		} else {
			// public
			$this->output( 'making public...' );
			$status->merge( $backend->publish( [ 'dir' => $dir, 'access' => true ] ) );
		}

		if ( $status->isOK() ) {
			$this->output( "done.\n" );
		} else {
			$this->output( "failed.\n" );
			print_r( $status->getErrors() );
		}
	}
}

$maintClass = SetContainersAccess::class;
require_once RUN_MAINTENANCE_IF_MAIN;

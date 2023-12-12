<?php

$wgHooks['CreateWikiJsonGenerateDatabaseList'][] = 'onGenerateDatabaseLists';

function getCombiList(): array {
	$db = wfInitDBConnection();
	$db->selectDomain( 'wikidb' );
	$allWikis = $db->newSelectQueryBuilder()
		->table( 'cw_wikis' )
		->fields( [
			'wiki_dbcluster',
			'wiki_dbname',
			'wiki_url',
			'wiki_sitename',
		] )
		->where( [ 'wiki_deleted' => 0 ] )
		->caller( __METHOD__ )
		->fetchResultSet();

	$combiList = [];
	foreach ( $allWikis as $wiki ) {
		$combiList[$wiki->wiki_dbname] = [
			's' => $wiki->wiki_sitename,
			'c' => $wiki->wiki_dbcluster,
		];

		if ( $wiki->wiki_url !== null ) {
			$combiList[$wiki->wiki_dbname]['u'] = $wiki->wiki_url;
		}
	}

	return $combiList;
}

function onGenerateDatabaseLists( array &$databaseLists ) {
	$databaseLists = [
		'databases' => [
			'combi' => getCombiList(),
		],
	];
}

?>

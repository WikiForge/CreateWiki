<?php

namespace WikiForge\CreateWiki\Hooks;

interface CreateWikiStateClosedHook {
	/**
	 * @param string $dbname
	 * @return void
	 */
	public function onCreateWikiStateClosed( $dbname ): void;
}

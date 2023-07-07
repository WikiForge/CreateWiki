<?php

namespace WikiForge\CreateWiki\Hooks;

interface CreateWikiWritePersistentModelHook {
	/**
	 * @param string $pipeline
	 * @return bool
	 */
	public function onCreateWikiWritePersistentModel( $pipeline ): bool;
}

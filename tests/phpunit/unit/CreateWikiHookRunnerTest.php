<?php

namespace WikiForge\CreateWiki\Tests\Unit;

use MediaWiki\Tests\HookContainer\HookRunnerTestBase;
use WikiForge\CreateWiki\Hooks\CreateWikiHookRunner;

/**
 * @covers \WikiForge\CreateWiki\Hooks\CreateWikiHookRunner
 */
class CreateWikiHookRunnerTest extends HookRunnerTestBase {

	public function provideHookRunners() {
		yield CreateWikiHookRunner::class => [ CreateWikiHookRunner::class ];
	}
}

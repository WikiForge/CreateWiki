<?php

namespace WikiForge\CreateWiki\Tests;

use DerivativeContext;
use MediaWikiIntegrationTestCase;
use SpecialPage;
use UserNotLoggedIn;
use WikiForge\CreateWiki\Hooks\CreateWikiHookRunner;
use WikiForge\CreateWiki\RequestWiki\SpecialRequestWiki;
use Wikimedia\TestingAccessWrapper;

/**
 * @group CreateWiki
 * @group Database
 * @group Medium
 * @coversDefaultClass \WikiForge\CreateWiki\RequestWiki\SpecialRequestWiki
 */
class SpecialRequestWikiTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstructor() {
		$hookRunner = $this->createMock( CreateWikiHookRunner::class );
		$specialRequestWiki = new SpecialRequestWiki( $hookRunner );

		$this->assertInstanceOf( SpecialRequestWiki::class, $specialRequestWiki );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecuteNotLoggedIn() {
		$hookRunner = $this->createMock( CreateWikiHookRunner::class );
		$specialRequestWiki = new SpecialRequestWiki( $hookRunner );

		$this->expectException( UserNotLoggedIn::class );
		$specialRequestWiki->execute( '' );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecuteLoggedIn() {
		$this->setGroupPermissions( 'user', 'requestwiki', true );

		$hookRunner = $this->createMock( CreateWikiHookRunner::class );

		$specialRequestWiki = TestingAccessWrapper::newFromObject(
			new SpecialRequestWiki( $hookRunner )
		);

		$testContext = new DerivativeContext( $specialRequestWiki->getContext() );

		$testContext->setUser( $this->getTestUser()->getUser() );
		$testContext->setTitle( SpecialPage::getTitleFor( 'RequestWiki' ) );

		$specialRequestWiki->setContext( $testContext );

		$this->assertNull( $specialRequestWiki->execute( '' ) );
	}

	/**
	 * @covers ::getFormFields
	 */
	public function testGetFormFields() {
		$hookRunner = $this->createMock( CreateWikiHookRunner::class );

		$specialRequestWiki = TestingAccessWrapper::newFromObject(
			new SpecialRequestWiki( $hookRunner )
		);

		$this->assertArrayHasKey( 'subdomain', $specialRequestWiki->getFormFields() );
		$this->assertArrayHasKey( 'sitename', $specialRequestWiki->getFormFields() );
		$this->assertArrayHasKey( 'language', $specialRequestWiki->getFormFields() );
	}

	/**
	 * Data provider for testOnSubmit
	 *
	 * @return array
	 */
	public function onSubmitDataProvider() {
		return [
			[
				[
					'reason' => 'Test onSubmit()',
					'subdomain' => 'example',
					'sitename' => 'Example Wiki',
					'language' => 'en',
					'category' => 'uncategorized',
				],
				true,
			],
			[
				[
					'reason' => 'Test onSubmit()',
					'subdomain' => 'example',
					'sitename' => 'Example Wiki',
					'language' => 'en',
					'category' => 'uncategorized',
				],
				false,
			],
		];
	}

	/**
	 * @dataProvider onSubmitDataProvider
	 * @covers ::onSubmit
	 * @param array $formData
	 * @param bool $expected
	 */
	public function testOnSubmit( $formData, $expected ) {
		$this->setMwGlobals( 'wgCreateWikiSubdomain', 'miraheze.org' );

		$hookRunner = $this->createMock( CreateWikiHookRunner::class );
		$specialRequestWiki = new SpecialRequestWiki( $hookRunner );

		$submitData = $specialRequestWiki->onSubmit( $formData );

		$this->assertSame( $expected, $submitData );
	}
}

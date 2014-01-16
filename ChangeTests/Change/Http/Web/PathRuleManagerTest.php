<?php
namespace ChangeTests\Change\Http\Web;

use Change\Http\Web\PathRuleManager;

class PathRuleManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDb();
		static::initDocumentsClasses();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @return PathRuleManager
	 */
	protected function getPathRuleManager()
	{
		return $this->getApplicationServices()->getPathRuleManager();
	}

	public function testGetNewRule()
	{
		$prm = $this->getPathRuleManager();
		$pathRule = $prm->getNewRule(1000, 'fr_FR', 'test.html', 1010, 301, 1001, 'toto=1');
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule);
		$this->assertEquals(1000, $pathRule->getWebsiteId());
		$this->assertEquals('fr_FR', $pathRule->getLCID());
		$this->assertEquals(1010, $pathRule->getDocumentId());
		$this->assertEquals(301, $pathRule->getHttpStatus());
		$this->assertEquals(1001, $pathRule->getSectionId());
		$this->assertEquals('toto=1', $pathRule->getQuery());
		$this->assertNull($pathRule->getRuleId());
	}

	/**
	 * @depends testGetNewRule
	 */
	public function testInsertFindAndUpdatePathRules()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$prm = $this->getPathRuleManager();

		// Creation / find.
		$this->assertCount(0, $prm->findPathRules(1000, 'fr_FR', 1010, 1001));
		$this->assertCount(0, $prm->findRedirectedRules(1000, 'fr_FR', 1010, 1001));
		$pathRule1 = $prm->getNewRule(1000, 'fr_FR', 'test.html', 1010, 301, 1001, 'toto=1');
		$prm->insertPathRule($pathRule1);
		$this->assertGreaterThan(0, $pathRule1->getRuleId()); // The ruleId is set.
		$this->assertCount(0, $prm->findPathRules(1000, 'fr_FR', 1010, 1001));
		$rules = $prm->findRedirectedRules(1000, 'fr_FR', 1010, 1001);
		$this->assertCount(1, $rules);
		$this->assertSamePathRule($pathRule1, $rules[0]);

		// Update.
		// updatePathRule: Only HTTP status and query can be updated here.
		$pathRule1->setHttpStatus(200);
		$pathRule1->setQuery(null);
		$prm->updatePathRule($pathRule1);
		$rules = $prm->findPathRules(1000, 'fr_FR', 1010, 1001);
		$this->assertCount(1, $rules);
		$this->assertSamePathRule($pathRule1, $rules[0]);
		$this->assertCount(0, $prm->findRedirectedRules(1000, 'fr_FR', 1010, 1001));

		// updateRuleStatus
		$prm->updateRuleStatus($pathRule1->getRuleId(), 302);
		$this->assertCount(0, $prm->findPathRules(1000, 'fr_FR', 1010, 1001));
		$rules = $prm->findRedirectedRules(1000, 'fr_FR', 1010, 1001);
		$this->assertCount(1, $rules);
		$this->assertSamePathRule($prm->getNewRule(1000, 'fr_FR', 'test.html', 1010, 302, 1001), $rules[0]);

		// Deletion.
		$prm->updateRuleStatus($pathRule1->getRuleId(), 404);
		$this->assertCount(0, $prm->findPathRules(1000, 'fr_FR', 1010, 1001));
		$this->assertCount(0, $prm->findRedirectedRules(1000, 'fr_FR', 1010, 1001));

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testRewritePathRule()
	{
		$pathRuleManager = $this->getPathRuleManager();
		$rule = $pathRuleManager->getNewRule(1000, 'fr_FR', 'test.html', 4000, 200);
		$document = $this->getNewReadonlyDocument('Project_Tests_Correction', 4000);
		$pathRule = $pathRuleManager->populatePathRuleByDocument($rule, $document);
		$this->assertNull($pathRule);

		$callback = function (\Change\Events\Event $event) {
			$pathRule = $event->getParam('pathRule');
			$pathRule->setRelativePath('toto.html');
		};

		$pathRuleManager->getEventManager()->attach(PathRuleManager::EVENT_POPULATE_PATH_RULE, $callback);
		$pathRule = $pathRuleManager->populatePathRuleByDocument($rule, $document);
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule);
		$this->assertEquals('toto.html', $pathRule->getRelativePath());

	}


	/**
	 * @param \Change\Http\Web\PathRule $pathRule1
	 * @param \Change\Http\Web\PathRule $pathRule2
	 */
	protected function assertSamePathRule($pathRule1, $pathRule2)
	{
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule1);
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule2);
		$this->assertEquals($pathRule1->getWebsiteId(), $pathRule2->getWebsiteId());
		$this->assertEquals($pathRule1->getLCID(), $pathRule2->getLCID());
		$this->assertEquals($pathRule1->getDocumentId(), $pathRule2->getDocumentId());
		$this->assertEquals($pathRule1->getHttpStatus(), $pathRule2->getHttpStatus());
		$this->assertEquals($pathRule1->getSectionId(), $pathRule2->getSectionId());
		$this->assertEquals($pathRule1->getQuery(), $pathRule2->getQuery());
	}
}
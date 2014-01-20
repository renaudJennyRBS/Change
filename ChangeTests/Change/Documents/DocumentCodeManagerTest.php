<?php
namespace ChangeTests\Change\Documents;

/**
 * @name \ChangeTests\Change\Documents\DocumentCodeManagerTest
 */
class DocumentCodeManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testGetInstance()
	{
		$this->assertInstanceOf('Change\Documents\DocumentCodeManager',
			$this->getApplicationServices()->getDocumentCodeManager());
	}

	public function testAddDocumentCode()
	{
		$dcm = $this->getApplicationServices()->getDocumentCodeManager();
		$d1 = $this->getNewReadonlyDocument('Project_Tests_Basic', 120);

		$this->assertFalse($dcm->addDocumentCode(null, 'error'));
		$this->assertFalse($dcm->addDocumentCode(1, ''));

		$res = $dcm->addDocumentCode($d1, 'd120');
		$this->assertGreaterThan(0, $res);

		$res2 = $dcm->addDocumentCode(130, 'd130', 5);
		$this->assertGreaterThan($res, $res2);

		$res3 = $dcm->addDocumentCode(120, 'd120');
		$this->assertEquals($res, $res3);

		$this->assertGreaterThan($res, $dcm->addDocumentCode(120, 'dup'));
		$this->assertGreaterThan($res, $dcm->addDocumentCode(130, 'dup'));
	}

	/**
	 * @depends testAddDocumentCode
	 */
	public function testDocumentsByCode()
	{
		$dcm = $this->getApplicationServices()->getDocumentCodeManager();
		$d120 = $this->getNewReadonlyDocument('Project_Tests_Basic', 120);
		$d130 = $this->getNewReadonlyDocument('Project_Tests_Basic', 130);

		$arr = $dcm->getDocumentsByCode('d120');
		$this->assertCount(1, $arr);
		$this->assertArrayHasKey(0, $arr);
		$this->assertSame($d120, $arr[0]);

		$arr = $dcm->getDocumentsByCode('d130', 5);
		$this->assertCount(1, $arr);
		$this->assertArrayHasKey(0, $arr);
		$this->assertSame($d130, $arr[0]);

		$arr = $dcm->getDocumentsByCode('not_found');
		$this->assertCount(0, $arr);

		$arr = $dcm->getDocumentsByCode('dup');
		$this->assertCount(2, $arr);
	}

	/**
	 * @depends testDocumentsByCode
	 */
	public function testCodesByDocument()
	{
		$dcm = $this->getApplicationServices()->getDocumentCodeManager();
		$d120 = $this->getNewReadonlyDocument('Project_Tests_Basic', 120);

		$arr = $dcm->getCodesByDocument($d120);
		$this->assertCount(2, $arr);
		$this->assertContains('d120', $arr);
		$this->assertContains('dup', $arr);

		$arr = $dcm->getCodesByDocument(130, 5);
		$this->assertCount(1, $arr);
		$this->assertContains('d130', $arr);

		$arr = $dcm->getCodesByDocument(1);
		$this->assertCount(0, $arr);
	}

	/**
	 * @depends testCodesByDocument
	 */
	public function testRemoveDocumentCode()
	{
		$dcm = $this->getApplicationServices()->getDocumentCodeManager();
		$d120 = $this->getNewReadonlyDocument('Project_Tests_Basic', 120);

		$this->assertFalse($dcm->removeDocumentCode(null, 'error'));
		$this->assertFalse($dcm->removeDocumentCode(1, ''));

		$res = $dcm->removeDocumentCode($d120, 'd120');
		$this->assertGreaterThan(0, $res);

		$res = $dcm->removeDocumentCode(130, 'd130', 5);
		$this->assertGreaterThan(0, $res);

		$this->assertTrue($dcm->removeDocumentCode(120, 'd120'));
	}

	public function testContext()
	{
		$dcm = $this->getApplicationServices()->getDocumentCodeManager();
		$dcm->addDocumentCode(1000, 'code_1', 0);
		$dcm->addDocumentCode(1000, 'code_2', '');
		$dcm->addDocumentCode(1000, 'code_3', 'Context Name');

		$contexts = $dcm->getDocumentContexts(1000);
		$this->assertEquals(['', 'Context Name'], $contexts);
		$codes = $dcm->getCodesByDocument(1000, '');
		$this->assertCount(2, $codes);
		$this->assertContains('code_1', $codes);
		$this->assertContains('code_2', $codes);
		$this->assertEquals(['code_3'], $dcm->getCodesByDocument(1000, 'Context Name'));
		$this->assertCount(0, $dcm->getCodesByDocument(1000, 'Not Found'));
	}

	/**
	 * @depends testContext
	 */
	public function testQuery()
	{
		$JSONDecoder = new \Change\Documents\Query\JSONDecoder();
		$JSONDecoder->setDocumentManager($this->getApplicationServices()->getDocumentManager());
		$JSONDecoder->setModelManager($this->getApplicationServices()->getModelManager());

		$query = $JSONDecoder->getQuery(['model' => 'Project_Tests_Basic', 'where' =>['and' => [['op' => 'HasCode', 'code' => 'code_1', 'context' => 'Context Name']]]]);
		$sql92 = $query->dbQueryBuilder()->query()->toSQL92String();
		$this->assertEquals('SELECT * FROM "project_tests_doc_basic" AS "_t0" WHERE ((EXISTS(SELECT * FROM "change_document_code" WHERE ("change_document_code"."document_id" = "_t0"."document_id" AND "change_document_code"."code" = :_p1 AND "change_document_code"."context_id" = :_p2))))', $sql92);
	}
} 
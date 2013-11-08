<?php
namespace ChangeTests\RBS\Tag\Db\Query;

use Change\Db\Query;
use Rbs\Tag\Db\Query\HasTag;

class HasTagTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	public function testCheckCompile()
	{
		$obj = new HasTag();
		try
		{
			$obj->checkCompile();
			$this->fail('Exception Expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid documentId column Expression', $e->getMessage());
		}
		$obj->setDocumentIdColumn(new Query\Expressions\Column(new Query\Expressions\Identifier(array('c1'))));
		try
		{
			$obj->checkCompile();
			$this->fail('Exception Expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid TagId Expression', $e->getMessage());
		}
		$obj->setTagId(new Query\Expressions\Identifier(array('tag')));
		$obj->checkCompile();
	}

	public function testToSQL92String()
	{
		$obj = new HasTag();
		$obj->setDocumentIdColumn(new Query\Expressions\Column(new Query\Expressions\Identifier(array('c1'))));
		$obj->setTagId(new Query\Expressions\Identifier(array('tag')));
		$sql = 'EXISTS(SELECT * FROM "rbs_tag_document" INNER JOIN "rbs_tag_search" USING ("tag_id") WHERE ("doc_id" = "c1" AND "search_tag_id" = "tag"))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}

	public function testPopulate()
	{
		$obj = new HasTag();

		$predicateJSON = array('tag' => 1);
		$JSONDecoder = new \Change\Documents\Query\JSONDecoder();
		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Tag_Tag');
		$JSONDecoder->setDocumentQuery($query);
		$predicateBuilder = $query->getPredicateBuilder();

		$fragment = $obj->populate($predicateJSON, $JSONDecoder, $predicateBuilder);
		$sql = 'EXISTS(SELECT * FROM "rbs_tag_document" INNER JOIN "rbs_tag_search" USING ("tag_id") WHERE ("doc_id" = "_t0"."document_id" AND "search_tag_id" = :_p1))';
		$this->assertEquals($sql, $fragment->toSQL92String());
	}

	public function testJSONDecode()
	{
		$json = array('model' => 'Rbs_Generic_Folder', 'where' => array('and' => array(array('op' => 'hasTag', 'tag' => 1))));
		$applicationServices = $this->getApplicationServices();
		$dbProvider = $applicationServices->getDbProvider();
		$la = new \Rbs\Generic\Events\Db\Listeners();
		$la->attach($dbProvider->getEventManager());

		$JSONDecoder = new \Change\Documents\Query\JSONDecoder();
		$JSONDecoder->setDocumentManager($applicationServices->getDocumentManager())
			->setModelManager($applicationServices->getModelManager())
			->setTreeManager($applicationServices->getTreeManager());
		$query = $JSONDecoder->getQuery($json);

		$sql= 'SELECT * FROM "rbs_generic_doc_folder" AS "_t0" WHERE ((EXISTS(SELECT * FROM "rbs_tag_document" INNER JOIN "rbs_tag_search" USING ("tag_id") WHERE ("doc_id" = "_t0"."document_id" AND "search_tag_id" = :_p1))))';
		$this->assertEquals($sql,$query->dbQueryBuilder()->query()->toSQL92String());
	}

	/**
	 * @depends testJSONDecode
	 */
	public function testQuery()
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$obj = new HasTag();
		$obj->setDocumentIdColumn($fb->getDocumentColumn('id'));
		$obj->setTagId($fb->number(10000));
		$qb->select('c1')->from('table')->where($obj);

		$sql = 'SELECT "c1" FROM "table" WHERE EXISTS(SELECT * FROM "rbs_tag_document" INNER JOIN "rbs_tag_search" USING ("tag_id") WHERE ("doc_id" = "document_id" AND "search_tag_id" = 10000))';
		$this->assertEquals($sql, $qb->query()->toSQL92String());
	}
}
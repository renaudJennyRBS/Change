<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\JSONDecoder;

/**
* @name \ChangeTests\Change\Documents\Query\JSONDecoderTest
*/
class JSONDecoderTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public static function tearDownAfterClass()
	{
		//static::clearDB();
	}

	/**
	 * @return JSONDecoder
	 */
	protected function getObject()
	{
		$o = new JSONDecoder();
		$o->setDocumentServices($this->getDocumentServices());
		return $o;
	}

	public function testGetQuery()
	{
		$o = $this->getObject();
		$json = file_get_contents(__DIR__ . '/TestAssets/json1.json');
		$query = $o->getQuery($json);
		$expected = 'SELECT * FROM "project_tests_doc_basic" AS "_t0" INNER JOIN "project_tests_rel_basic" AS "_r1R" ON ("_t0"."document_id" = "_r1R"."document_id" AND "_r1R"."relname" = \'pDocArr\') WHERE (("_r1R"."relatedid" = :_p2)) ORDER BY "_t0"."pstr" ASC';
		$this->assertEquals($expected, $query->dbQueryBuilder()->query()->toSQL92String());
	}

	public function testJoinGetQuery()
	{
		$o = $this->getObject();
		$json = file_get_contents(__DIR__ . '/TestAssets/json2.json');
		$query = $o->getQuery($json);
		$expected = 'SELECT * FROM "project_tests_doc_basic" AS "_t0" INNER JOIN "project_tests_doc_localized" AS "_t1" ON "_t1"."document_id" = "_t0"."pdocinst" INNER JOIN "project_tests_doc_basic" AS "_t2" ON "_t2"."pint" = "_t1"."pint" WHERE (("_t1"."pfloat" > :_p3)) ORDER BY "_t2"."pstr" ASC';
		$this->assertEquals($expected, $query->dbQueryBuilder()->query()->toSQL92String());
	}
}
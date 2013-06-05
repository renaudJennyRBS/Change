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
		$json = '{"model":"Project_Tests_Basic","where":{"and":[{"op":"eq","lexp":{"property":"pDocArr"},"rexp":{"value":100010}}]},"order":[{"property":"pStr","order":"asc"}]}';
		$query = $o->getQuery($json);
		$expected = 'SELECT * FROM "project_tests_doc_basic" AS "_t0" INNER JOIN "project_tests_rel_basic" AS "_r1R" ON ("_t0"."document_id" = "_r1R"."document_id" AND "_r1R"."relname" = \'pDocArr\') WHERE (("_r1R"."relatedid" = :_p2)) ORDER BY "_t0"."pstr" ASC';
		$this->assertEquals($expected, $query->dbQueryBuilder()->query()->toSQL92String());
	}
}
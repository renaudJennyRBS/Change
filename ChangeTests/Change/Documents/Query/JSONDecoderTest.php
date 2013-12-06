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
		$as = $this->getApplicationServices();
		$o->setDocumentManager($as->getDocumentManager())
			->setModelManager($as->getModelManager())
			->setTreeManager($as->getTreeManager());
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

	public function testPublishedQuery()
	{
		$o = $this->getObject();
		$json = file_get_contents(__DIR__ . '/TestAssets/json3.json');
		$query = $o->getQuery($json);
		$sq = $query->dbQueryBuilder()->query();

		$at = \DateTime::createFromFormat(\DateTime::ISO8601, '2013-07-04T15:04:08Z');
		$to = \DateTime::createFromFormat(\DateTime::ISO8601, '2013-08-04T15:04:08Z');
		$this->assertEquals($at, $sq->getParameterValue('_p2'));
		$this->assertEquals($to, $sq->getParameterValue('_p3'));

		$expected = 'SELECT * FROM "project_tests_doc_correction" AS "_t0" INNER JOIN "project_tests_doc_correction_i18n" AS "_t0L" ON ("_t0"."document_id" = "_t0L"."document_id" AND "_t0L"."lcid" = \'fr_FR\') WHERE ("_t0"."document_model" IN (\'Project_Tests_Correction\', \'Project_Tests_CorrectionExt\') AND (("_t0L"."publicationstatus" = :_p1 AND ("_t0L"."startpublication" IS NULL OR "_t0L"."startpublication" <= :_p2) AND ("_t0L"."endpublication" IS NULL OR "_t0L"."endpublication" > :_p3))))';
		$this->assertEquals($expected, $sq->toSQL92String());
	}

	public function testLocalizedQuery()
	{
		$o = $this->getObject();
		$json = file_get_contents(__DIR__ . '/TestAssets/json4.json');
		$query = $o->getQuery($json);
		$sq = $query->dbQueryBuilder()->query();
		$expected = 'SELECT * FROM "project_tests_doc_localized" AS "_t0" INNER JOIN "project_tests_doc_localized_i18n" AS "_t0L" ON ("_t0"."document_id" = "_t0L"."document_id" AND "_t0L"."lcid" = \'fr_FR\') INNER JOIN "project_tests_doc_localized" AS "_t1" ON "_t1"."document_id" = "_t0"."pdocid" INNER JOIN "project_tests_doc_localized_i18n" AS "_t1L" ON ("_t1"."document_id" = "_t1L"."document_id" AND "_t1L"."lcid" = \'en_US\') INNER JOIN "project_tests_doc_localized" AS "_t2" ON "_t2"."document_id" = "_t1"."pdocid" INNER JOIN "project_tests_doc_localized_i18n" AS "_t2L" ON ("_t2"."document_id" = "_t2L"."document_id" AND "_t2L"."lcid" = "_t2"."reflcid") WHERE (("_t1L"."plstr" = :_p3)) ORDER BY "_t2L"."plstr" ASC, "_t0L"."plstr" ASC';
		$this->assertEquals($expected, $sq->toSQL92String());
	}
}
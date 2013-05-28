<?php
namespace ChangeTests\Db;

use Change\Db\ScalarType;
use Change\Db\SqlMapping;
use Change\Documents\Property;

class SqlMappingTest extends \PHPUnit_Framework_TestCase
{
	
	public function testConstruct()
	{
		$sqlMapping = new SqlMapping();
		$this->assertTrue(true);
		return $sqlMapping;
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentTableName(SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_doc_page', $sqlMapping->getDocumentTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentRelationTableName(SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_rel_page', $sqlMapping->getDocumentRelationTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentI18nTableName(SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_doc_page_i18n', $sqlMapping->getDocumentI18nTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetTreeTableName(SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_tree', $sqlMapping->getTreeTableName('Change_Website'));
	}


	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentFieldName(SqlMapping $sqlMapping)
	{
		$this->assertEquals('document_id', $sqlMapping->getDocumentFieldName('id'));
		$this->assertEquals('document_model', $sqlMapping->getDocumentFieldName('model'));
		$this->assertEquals('treename', $sqlMapping->getDocumentFieldName('treeName'));

		$this->assertEquals('test_name', $sqlMapping->getDocumentFieldName('test_Name'));
		$this->assertEquals('test_name', $sqlMapping->getDocumentFieldName('TEST_NAME'));
	}


	/**
	 * @depends testConstruct
	 */
	public function testGetDbScalarType(SqlMapping $sqlMapping)
	{
		$this->assertEquals(ScalarType::BOOLEAN,
			$sqlMapping->getDbScalarType(Property::TYPE_BOOLEAN));

		$this->assertEquals(ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(Property::TYPE_DOCUMENT));
		$this->assertEquals(ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(Property::TYPE_DOCUMENTARRAY));
		$this->assertEquals(ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(Property::TYPE_DOCUMENTID));
		$this->assertEquals(ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(Property::TYPE_INTEGER));

		$this->assertEquals(ScalarType::DATETIME,
			$sqlMapping->getDbScalarType(Property::TYPE_DATE));
		$this->assertEquals(ScalarType::DATETIME,
			$sqlMapping->getDbScalarType(Property::TYPE_DATETIME));

		$this->assertEquals(ScalarType::DECIMAL,
			$sqlMapping->getDbScalarType(Property::TYPE_FLOAT));
		$this->assertEquals(ScalarType::DECIMAL,
			$sqlMapping->getDbScalarType(Property::TYPE_DECIMAL));

		$this->assertEquals(ScalarType::TEXT,
			$sqlMapping->getDbScalarType(Property::TYPE_JSON));
		$this->assertEquals(ScalarType::TEXT,
			$sqlMapping->getDbScalarType(Property::TYPE_LONGSTRING));
		$this->assertEquals(ScalarType::TEXT,
			$sqlMapping->getDbScalarType(Property::TYPE_RICHTEXT));
		$this->assertEquals(ScalarType::TEXT,
			$sqlMapping->getDbScalarType(Property::TYPE_XML));

		$this->assertEquals(ScalarType::LOB,
			$sqlMapping->getDbScalarType(Property::TYPE_LOB));
		$this->assertEquals(ScalarType::LOB,
			$sqlMapping->getDbScalarType(Property::TYPE_OBJECT));

		$this->assertEquals(ScalarType::STRING,
			$sqlMapping->getDbScalarType('Unknown'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentTableNames(SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_document', $sqlMapping->getDocumentIndexTableName());
		$this->assertEquals('change_document_metas', $sqlMapping->getDocumentMetasTableName());
		$this->assertEquals('change_document_deleted', $sqlMapping->getDocumentDeletedTable());
		$this->assertEquals('change_document_correction', $sqlMapping->getDocumentCorrectionTable());
		$this->assertEquals('change_oauth', $sqlMapping->getOAuthTable());
		$this->assertEquals('change_path_rule', $sqlMapping->getPathRuleTable());
		$this->assertEquals('change_plugin', $sqlMapping->getPluginTableName());
	}
}

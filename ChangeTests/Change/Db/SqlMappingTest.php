<?php
namespace ChangeTests\Db;

class SqlMappingTest extends \PHPUnit_Framework_TestCase
{
	
	public function testConstruct()
	{
		$sqlMapping = new \Change\Db\SqlMapping();
		$this->assertTrue(true);
		return $sqlMapping;
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentTableName(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_doc_page', $sqlMapping->getDocumentTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentRelationTableName(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_rel_page', $sqlMapping->getDocumentRelationTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentI18nTableName(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_doc_page_i18n', $sqlMapping->getDocumentI18nTableName('Change_Website_Page'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetTreeTableName(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_tree', $sqlMapping->getTreeTableName('Change_Website'));
	}


	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentFieldName(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('document_id', $sqlMapping->getDocumentFieldName('id'));
		$this->assertEquals('document_model', $sqlMapping->getDocumentFieldName('model'));
		$this->assertEquals('tree_name', $sqlMapping->getDocumentFieldName('treeName'));

		$this->assertEquals('test_name', $sqlMapping->getDocumentFieldName('test_Name'));
		$this->assertEquals('test_name', $sqlMapping->getDocumentFieldName('TEST_NAME'));
	}


	/**
	 * @depends testConstruct
	 */
	public function testGetDbScalarType(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals(\Change\Db\ScalarType::BOOLEAN,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_BOOLEAN));

		$this->assertEquals(\Change\Db\ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DOCUMENT));
		$this->assertEquals(\Change\Db\ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DOCUMENTARRAY));
		$this->assertEquals(\Change\Db\ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DOCUMENTID));
		$this->assertEquals(\Change\Db\ScalarType::INTEGER,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_INTEGER));

		$this->assertEquals(\Change\Db\ScalarType::DATETIME,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DATE));
		$this->assertEquals(\Change\Db\ScalarType::DATETIME,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DATETIME));

		$this->assertEquals(\Change\Db\ScalarType::DECIMAL,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_FLOAT));
		$this->assertEquals(\Change\Db\ScalarType::DECIMAL,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_DECIMAL));

		$this->assertEquals(\Change\Db\ScalarType::TEXT,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_JSON));
		$this->assertEquals(\Change\Db\ScalarType::TEXT,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_LONGSTRING));
		$this->assertEquals(\Change\Db\ScalarType::TEXT,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_RICHTEXT));
		$this->assertEquals(\Change\Db\ScalarType::TEXT,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_XML));

		$this->assertEquals(\Change\Db\ScalarType::LOB,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_LOB));
		$this->assertEquals(\Change\Db\ScalarType::LOB,
			$sqlMapping->getDbScalarType(\Change\Documents\Property::TYPE_OBJECT));

		$this->assertEquals(\Change\Db\ScalarType::STRING,
			$sqlMapping->getDbScalarType('Unknown'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetDocumentTableNames(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_document', $sqlMapping->getDocumentIndexTableName());
		$this->assertEquals('change_document_metas', $sqlMapping->getDocumentMetasTableName());
		$this->assertEquals('change_document_deleted', $sqlMapping->getDocumentDeletedTable());
		$this->assertEquals('change_document_correction', $sqlMapping->getDocumentCorrectionTable());
	}
}

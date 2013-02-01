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
	public function testFunctions(\Change\Db\SqlMapping $sqlMapping)
	{
		$this->assertEquals('change_website_doc_page', $sqlMapping->getDocumentTableName('Change_Website_Page'));
		$this->assertEquals('change_website_doc_page_i18n', $sqlMapping->getDocumentI18nTableName('Change_Website_Page'));
		$this->assertEquals('document_id', $sqlMapping->getDocumentFieldName('id'));
		$this->assertEquals('document_model', $sqlMapping->getDocumentFieldName('model'));
		
		$this->assertEquals('testname', $sqlMapping->getDocumentFieldName('testName'));
		
		$this->assertEquals('change_document', $sqlMapping->getDocumentIndexTableName());
		$this->assertEquals('change_document_metas', $sqlMapping->getDocumentMetasTableName());
		$this->assertEquals('change_document_deleted', $sqlMapping->getDocumentDeletedTable());

		$this->assertEquals('f_locale', $sqlMapping->getLocaleTableName());
		$this->assertEquals('f_settings', $sqlMapping->getSettingTableName());
		$this->assertEquals('f_tags', $sqlMapping->getTagsTableName());
	}	
}

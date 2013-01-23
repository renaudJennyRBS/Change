<?php
namespace Change\Db;

/**
 * @name \Change\Db\SqlMapping
 */
class SqlMapping
{	
	/**
	 * @api
	 * @param string $rootDocumentName
	 * @return string
	 */
	public function getDocumentTableName($rootDocumentName)
	{
		list($vendor, $module, $name) = explode('_', strtolower($rootDocumentName));
		return $vendor . '_' . $module . '_doc_' . $name;
	}
	
	
	/**
	 * @api
	 * @param string $rootDocumentName
	 * @return string
	 */
	public function getDocumentRelationTableName($rootDocumentName)
	{
		list($vendor, $module, $name) = explode('_', strtolower($rootDocumentName));
		return $vendor . '_' . $module . '_rel_' . $name;
	}
	
	/**
	 * @api
	 * @param string $rootDocumentName
	 * @return string
	 */
	public function getDocumentI18nTableName($rootDocumentName)
	{
		return $this->getDocumentTableName($rootDocumentName) . '_i18n';
	}
	
	/**
	 * @api
	 * @param string $moduleName
	 * @return string
	 */
	public function getTreeTableName($moduleName)
	{
		list($vendor, $module) = explode('_', strtolower($moduleName));
		return $vendor . '_' . $module . '_tree';
	}
		
	/**
	 * @api
	 * @param string $propertyName
	 * @return string
	 */
	public function getDocumentFieldName($propertyName)
	{
		$pn = strtolower($propertyName);
		switch ($pn)
		{
			case 'id':
				return 'document_id';
			case 'model':
				return 'document_model';
			case 'treename':
				return 'tree_name';
		}
		return $pn;
	}
	
	/**
	 * @api
	 * @param string $propertyType \Change\Documents\Property::TYPE_*
	 * @return integer \Change\Db\ScalarType::*
	 */
	public function getDbScalarType($propertyType)
	{
		switch ($propertyType)
		{
			case \Change\Documents\Property::TYPE_DOCUMENTARRAY:
			case \Change\Documents\Property::TYPE_DOCUMENT:
			case \Change\Documents\Property::TYPE_DOCUMENTID:
			case \Change\Documents\Property::TYPE_INTEGER:
				return \Change\Db\ScalarType::INTEGER;
	
			case \Change\Documents\Property::TYPE_BOOLEAN:
				return \Change\Db\ScalarType::BOOLEAN;
	
			case \Change\Documents\Property::TYPE_DATE:
			case \Change\Documents\Property::TYPE_DATETIME:
				return \Change\Db\ScalarType::DATETIME;
	
			case \Change\Documents\Property::TYPE_FLOAT:
			case \Change\Documents\Property::TYPE_DECIMAL:
				return \Change\Db\ScalarType::DECIMAL;
	
			case \Change\Documents\Property::TYPE_JSON:
			case \Change\Documents\Property::TYPE_LONGSTRING:
			case \Change\Documents\Property::TYPE_RICHTEXT:
			case \Change\Documents\Property::TYPE_XML:
				return \Change\Db\ScalarType::TEXT;
	
			case \Change\Documents\Property::TYPE_LOB:
			case \Change\Documents\Property::TYPE_OBJECT:
				return \Change\Db\ScalarType::LOB;
		}
		return \Change\Db\ScalarType::STRING;
	}
		
	/**
	 * @api
	 * @return string
	 */
	public function getDocumentIndexTableName()
	{
		return 'change_document';
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getDocumentMetasTableName()
	{
		return 'change_document_metas';
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getDocumentDeletedTable()
	{
		return 'change_document_deleted';
	}
	
	
	
	/**
	 * @return string
	 */
	public function getLocaleTableName()
	{
		return 'f_locale';
	}
	
	/**
	 * @return string
	 */
	public function getSettingTableName()
	{
		return 'f_settings';
	}
	
	/**
	 * @return string
	 */
	public function getTagsTableName()
	{
		return 'f_tags';
	}

	/**
	 * @return string
	 */
	public function getURLRulesTableName()
	{
		return 'f_url_rules';
	}
	
	/**
	 * @return string
	 */
	public function getIndexingStateTableName()
	{
		return 'f_indexing';
	}
	
	/**
	 * @return string
	 */
	public function getI18nSynchroStateTableName()
	{
		return 'f_i18n';
	}	
	
	public function getCompiledPermissionTableName()
	{
		return 'f_permission_compiled';
	}
}
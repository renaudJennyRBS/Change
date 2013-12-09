<?php
namespace Change\Db;

/**
 * @name \Change\Db\SqlMapping
 * @api
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
	 * Convert Property name in table field name
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
		}
		return $pn;
	}
	
	/**
	 * Convert \Change\Documents\Property::TYPE_* in \Change\Db\ScalarType::*
	 * @api
	 * @param string $propertyType \Change\Documents\Property::TYPE_*
	 * @return integer
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
			case \Change\Documents\Property::TYPE_STORAGEURI:
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
	public function getPluginTableName()
	{
		return 'change_plugin';
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
	 * @api
	 * @return string
	 */
	public function getDocumentCorrectionTable()
	{
		return 'change_document_correction';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getOAuthTable()
	{
		return 'change_oauth';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getOAuthApplicationTable()
	{
		return 'change_oauth_application';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getPathRuleTable()
	{
		return 'change_path_rule';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getJobTable()
	{
		return 'change_job';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getPermissionRuleTable()
	{
		return 'change_permission_rule';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getWebPermissionRuleTable()
	{
		return 'change_web_permission_rule';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getUserAccountRequestTable()
	{
		return 'change_user_account_request';
	}
}
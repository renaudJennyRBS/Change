<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\SqlMapping
 */
class SqlMapping
{
	/**
	 * @return string
	 */
	public function getI18nSuffix()
	{
		return '_i18n';
	}
	
	/**
	 * @param string $documentName
	 * @return string
	 */
	public function getDocumentTableName($documentName)
	{
		list($vendor, $module, $name) = explode('_', strtolower($documentName));
		return $vendor . '_' . $module . '_doc_' . $name;
	}
	
	/**
	 * @param string $documentName
	 * @return string
	 */
	public function getDocumentI18nTableName($documentName)
	{
		return $this->getDocumentTableName($documentName) . $this->getI18nSuffix();
	}
		
	/**
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
	 * @param string $name
	 * @param string $nameSpace
	 * @param string $alias
	 * @return string
	 */
	public function escapeName($name, $nameSpace = null, $alias = null)
	{
		$sql = ($nameSpace ? '`' . $nameSpace . '`.`' : '`') . $name . '`';
		return ($alias) ?  $sql . ' AS `' . $alias . '`' : $sql;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function escapeParameterName($name)
	{
		return ':p' . $name;
	}
	
	/**
	 * @return string
	 */
	public function getDocumentIndexTableName()
	{
		return 'f_document';
	}
	
	/**
	 * @return string
	 */
	public function getRelationTableName()
	{
		return 'f_relation';
	}
	
	/**
	 * @return string
	 */
	public function getRelationNameTableName()
	{
		return 'f_relationname';
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
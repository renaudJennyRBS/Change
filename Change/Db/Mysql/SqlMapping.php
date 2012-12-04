<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\SqlMapping
 */
class SqlMapping
{
	/**
	 * @var string[]
	 */
	protected $i18nfieldNames;
	
	/**
	 * @return string[]
	 */
	public function getI18nFieldNames()
	{
		if ($this->i18nfieldNames === null)
		{
			$array = array('lang_vo');
			foreach (\Change\Application::getInstance()->getApplicationServices()->getI18nManager()->getSupportedLanguages() as $lang)
			{
				$array[] = 'label_'.$lang;
			}
			$this->i18nfieldNames = $array;
		}
		return $this->i18nfieldNames;
	}
	
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
	 * @param string $documentTableName
	 * @return string
	 */
	public function getDocumentI18nTableName($documentTableName)
	{
		return $documentTableName . $this->getI18nSuffix();
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	public function getDocumentTableNameByModel($model)
	{
		$names = $model->getAncestorModelNames();
		return (count($names)) ? $this->getDocumentTableName($names[0]->getName()) : $this->getDocumentTableName($model->getName());
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	public function getDocumentI18nTableNameByModel($model)
	{
		$documentTableName = $this->getDocumentTableNameByModel($model);
		return $this->getDocumentI18nTableName($documentTableName);
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
			case 'lang':
				return 'document_lang';
			case 'correctionofid':
				return 'document_correctionofid';
			case 'documentversion':
				return 'document_version';
			case 'metastring':
				return 'document_metas';
			case 's18s':
				return 'document_s18s';
			case 'label':
			case 'author':
			case 'authorid':
			case 'creationdate':
			case 'modificationdate':
			case 'publicationstatus':
			case 'modelversion':
			case 'startpublicationdate':
			case 'endpublicationdate':
			case 'correctionid':
				return 'document_' . $pn;
		}
		return $pn;
	}
	
	/**
	 * @param \Change\Documents\Property $property
	 * @return string
	 */
	public function getDocumentFieldNameByProperty($property)
	{
		$propertyName = $property->getDbMapping() ? $property->getDbMapping() : $property->getName();
		return $this->getDocumentFieldName($propertyName);
	}

	/**
	 * @param string $propertyName
	 * @return string
	 */
	public function getDocumentI18nFieldName($propertyName)
	{
		$pn = strtolower($propertyName);
		switch ($pn)
		{
			case 'id':
				return 'document_id';
			case 'lang':
				return 'lang_i18n';
			case 'label':
			case 'author':
			case 'authorid':
			case 'creationdate':
			case 'modificationdate':
			case 'publicationstatus':
			case 'modelversion':
			case 'startpublicationdate':
			case 'endpublicationdate':
			case 'correctionid':
				return 'document_' . $pn . $this->getI18nSuffix();
		}
		return $pn . $this->getI18nSuffix();
	}
	
	/**
	 * @param \Change\Documents\Property $property
	 * @return string
	 */
	public function getDocumentI18nFieldNameByProperty($property)
	{
		$propertyName = $property->getDbMapping() ? $property->getDbMapping() : $property->getName();
		return $this->getDocumentI18nFieldName($propertyName);
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
	 * @deprecated
	 */
	public function getDbNameByProperty($property, $localised = null)
	{
		$l = $localised === null ? $property->getLocalized() : $localised;
		$pn = strtolower($property->getName());
		switch ($pn)
		{
			case 'id':
				return 'document_id';
			case 'model':
				return 'document_model';
			case 'lang':
				return $l ? 'lang_i18n' : 'document_lang';
			case 'correctionofid':
				return 'document_correctionofid';
			case 'documentversion':
				return 'document_version';
			case 'metastring':
				return 'document_metas';
			case 's18s':
				return 'document_s18s';
			case 'label':
			case 'author':
			case 'authorid':
			case 'creationdate':
			case 'modificationdate':
			case 'publicationstatus':
			case 'modelversion':
			case 'startpublicationdate':
			case 'endpublicationdate':
			case 'correctionid':
				return $l ? 'document_' . $pn . '_i18n' : 'document_' . $pn ;
		}
		$l = $localised === null ? $property->getLocalized() : $localised;
		if ($property->getDbMapping()) {$pn = $property->getDbMapping();}
		return $l ? $pn . '_i18n' : $pn;
	} 
	
	/**
	 * @deprecated
	 */
	public function getDbNameByModel($model, $localised = false)
	{
		if ($localised)
		{
			return $model->getTableName() . '_i18n';
		}
		else
		{
			return $model->getTableName();
		}
	}
}
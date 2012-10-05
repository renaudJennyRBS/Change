<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentHelper
 */
class DocumentHelper
{
	/**
	 * Checks if $a equals $b.
	 *
	 * @param \Change\Documents\AbstractDocument $a
	 * @param \Change\Documents\AbstractDocument $b
	 * @return boolean
	 */
	public static function equals($a, $b)
	{
		if ($a === $b)
		{
			return true;
		}

		if ($a instanceof \Change\Documents\AbstractDocument && $b instanceof \Change\Documents\AbstractDocument)
		{
			return ($a->getId() === $b->getId() && $a->getId() > 0);
		}
		return false;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument[] $a
	 * @param \Change\Documents\AbstractDocument[] $b
	 */
	public static function documentArrayEquals($a, $b)
	{
		if ($a === $b) {return true;}
		if ($a instanceof \ArrayObject)
		{
			$a = $a->getArrayCopy();
		}
		elseif (!is_array($a)) 
		{
			return false;
		}
		
		if ($b instanceof \ArrayObject)
		{
			$b = $b->getArrayCopy();
		}		
		elseif (!is_array($b)) 
		{
			return false;
		}
		
		if (count($a) === count($b))
		{
			for ($i = 0; $i < count($a); $i++) 
			{
				if (!self::equals($a[i], $b[i])) {return false;}
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns the document instance or throws an exception.
	 * If you are in the context of a DocumentService, please use $this->getDocumentInstance().
	 * If you expect a given model name, please use static getInstanceById() on the final document class.
	 * @param integer $id
	 * @param string $modelName
	 * @return \Change\Documents\AbstractDocument
	 * @throws \Exception
	 */
	public static function getDocumentInstance($id, $modelName = null)
	{
		return \Change\Db\Provider::getInstance()->getDocumentInstance($id, $modelName);
	}

	/**
	 * Returns the document instance or null if the document does not exist.
	 * Please check the retuned value with instanceof.
	 * @param integer $id
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public static function getDocumentInstanceIfExists($id)
	{
		if (!is_numeric($id) || $id <= 0)
		{
			return null;
		}
		return \Change\Db\Provider::getInstance()->getDocumentInstanceIfExist($id);
	}
	
	/**
	 * Returns an array of IDs from an array of PersistentDocuments.
	 *
	 * @param \Change\Documents\AbstractDocument[] $documents
	 * @return integer[]
	 */
	public static function getIdArrayFromDocumentArray($documents)
	{
		if (is_array($documents))
		{
			return array_map(function($document) {
				if ($document instanceof \Change\Documents\AbstractDocument)
				{
					return $document->getId();
				}
				throw new \InvalidArgumentException('document not a "\Change\Documents\AbstractDocument" ');
			}, $documents);
		}
		return array();
	}
	
	/**
	 * Returns an array of PersistentDocuments from an array of IDs.
	 *
	 * @param integer[] $documentIds
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public static function getDocumentArrayFromIdArray($documentIds)
	{
		if (is_array($documentIds))
		{
			return array_map(function($id) {
				return \Change\Db\Provider::getInstance()->getDocumentInstance($id);
			}, $documentIds);
		}
		return array();
	}
	
	/**
	 * Return the orignal document for the $correctionId if exist. 
	 * If has no correction return the document instance of $correctionId
	 *
	 * @param integer $correctionId
	 * @return \Change\Documents\AbstractDocument
	 */
	public static function getByCorrectionId($correctionId)
	{
		return self::getByCorrection(self::getDocumentInstance($correctionId));
	}

	/**
	 * Return the orignal document for the $correction if exist. 
	 * If has no correction return the $correction document 
	 * 
	 * @param \Change\Documents\AbstractDocument $correction
	 * @return \Change\Documents\AbstractDocument
	 */
	public static function getByCorrection($correction)
	{
		if ($correction instanceof \Change\Documents\AbstractDocument) 
		{
			$model = $correction->getPersistentModel();
			if ($model->useCorrection())
			{
				if ($correction->getCorrectionofid() > 0)
				{
					return self::getDocumentInstance($correction->getCorrectionofid());
				}
			}  
		}
		return $correction;
	}

	/**
	 * Return the correction document for the $documentId if exist. 
	 * If has no correction return the originadocument document 
	 * 
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 */
	public static function getCorrectionById($documentId)
	{
		return self::getCorrection(self::getDocumentInstance($documentId));
	}
	
	/**
	 * Return the correction document for the $document if exist. 
	 * If has no correction return the $document document 
	 * 
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Documents\AbstractDocument
	 */
	public static function getCorrection($document)
	{
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			if ($document->isContextLangAvailable() 
				&& $document->getPersistentModel()->useCorrection()
				&& $document->getCorrectionid() > 0)
			{
				return self::getDocumentInstance($document->getCorrectionid());
			}
		}
		return $document;
	}
	
	
	/**
	 * Returns the properties values of the given $document.
	 *
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $lang
	 * @return array<propertyName => propertyValue>
	 */
	public static function getPropertiesOf($document, $lang = null)
	{
		$properties = array();
		$propertiesInfo = $document->getPersistentModel()->getEditablePropertiesInfos();
		foreach ($propertiesInfo as $propertyName => $propertyInfo)
		{
			/* @var $propertyInfo \Change\Documents\Property */
			if (!$propertyInfo->isDocument() && $propertyName != 'id' && $propertyName != 'model')
			{
				$getter = 'get'.ucfirst($propertyName);
				if (!is_null($lang) && $propertyInfo->isLocalized())
				{
					$properties[$propertyName] = $document->{$getter.'ForLang'}($lang);
				}
				else
				{
					$properties[$propertyName] = $document->{$getter}();
				}
			}
		}
		return $properties;
	}


	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string[] $propertiesNames
	 * @return array<propertyName => propertyValue>
	 */
	public static function getPropertiesListOf($document, $propertiesNames = null)
	{
		$allProperties = self::getPropertiesOf($document);
		if (count($propertiesNames) == 0)
		{
			return $allProperties;
		}
		$result = array();
		foreach ($propertiesNames as $propertyName)
		{
			if (array_key_exists($propertyName, $allProperties))
			{
				$result[$propertyName] = $allProperties[$propertyName];
			}
		}
		return $result;
	}


	/**
	 * Sets $properties to the $document.
	 *
	 * @param array<string, mixed> $properties
	 * @param \Change\Documents\AbstractDocument $document
	 * @param boolean $uivalues
	 */
	public static function setPropertiesTo($properties, $document, $uiValues = false)
	{
		$provider = \Change\Db\Provider::getInstance();
		$model = $document->getPersistentModel();

		foreach ($properties as $propertyName => $propertyValue)
		{
			if (($propertyName == 'lang') && ($propertyValue != $document->getI18nInfo()->getVo()) )
			{
				continue;
			}

			// If the value is REALLY empty, set it to null (and not empty array, empty string...)
			if ($propertyValue === '')
			{
				$propertyValue = null;
			}
			$property = $model->getEditableProperty($propertyName);
		
			if ($property === null)
			{
				$propertySet = false;
				$methodName = 'set'.ucfirst($propertyName);
				if (method_exists($document, $methodName))
				{
					call_user_func(array($document, $methodName), $propertyValue);
				}
			}
			else
			{
				// Call the "setter".
				if ($property->isArray())
				{
					$methodName = 'removeAll' . ucfirst($propertyName);
					call_user_func(array($document, $methodName));
					
					if (is_array($propertyValue))
					{
						$methodName = 'add' . ucfirst($propertyName);
						foreach ($propertyValue as $value)
						{
							if (is_numeric($value))
							{
								$value = self::getDocumentInstanceIfExists($value);
							}
							if ($value instanceof \Change\Documents\AbstractDocument)
							{
								call_user_func(array($document, $methodName), $value);
							}
							else
							{
								\Change\Application\LoggingManager::getInstance()->warn(__METHOD__ . ' Invalid value for ' . get_class($document) . '->' . $methodName);
							}
						}
					}
				}
				elseif ($property->isDocument())
				{
					$methodName = 'set' . ucfirst($propertyName);
					if (is_numeric($propertyValue))
					{
						$propertyValue = self::getDocumentInstanceIfExists($propertyValue);
					}
					
					if ($propertyValue instanceof \Change\Documents\AbstractDocument)
					{
						call_user_func(array($document, $methodName), $propertyValue);
					}
					else
					{
						\Change\Application\LoggingManager::getInstance()->warn(__METHOD__ . ' Invalid value for ' . get_class($document) . '->' . $methodName);
					}
				}
				else
				{
					$methodName = 'set' . ucfirst($propertyName);
					if ($property->getType() == \Change\Documents\AbstractDocument::PROPERTYTYPE_BOOLEAN)
					{
						//TODO Old class Usage
						$propertyValue = \f_util_Convert::toBoolean($propertyValue);
					}
					elseif ($uiValues)
					{
						if ($property->getType() == \Change\Documents\AbstractDocument::PROPERTYTYPE_DATETIME)
						{
							$methodName = 'setUI' . ucfirst($propertyName);
						} 
						elseif ($property->getType() == \Change\Documents\AbstractDocument::PROPERTYTYPE_DOUBLE)
						{
							//TODO Old class Usage
							$propertyValue = \f_util_Convert::parseUIDouble($propertyValue);  
						} 
						elseif ($property->getType() == \Change\Documents\AbstractDocument::PROPERTYTYPE_XHTMLFRAGMENT)
						{
							//TODO Old class Usage
							$propertyValue = \website_XHTMLCleanerHelper::clean($propertyValue);
						}
					}
					call_user_func(array($document, $methodName), $propertyValue);
				}
			}
		}
	}

	/**
	 * @return string[] properties that are handled by system and normally not edited by user
	 */
	public static function getSystemPropertyNames()
	{
		return array('id', 'model', 'author', 'authorid',
				'creationdate','modificationdate','publicationstatus',
				'lang','metastring','modelversion','documentversion', 'si18n');
	}

	/**
	 * For example: "[modules_generic/folder],!modules_generic/rootfolder"
	 * 				"modules_generic/folder,modules_generic/systemfolder"
	 * @param string $modelList
	 * @return string[]
	 */
	public static function expandModelList($modelList)
	{
		$models = array();
		//TODO Old class Usage
		$modelsChildren = \f_persistentdocument_PersistentDocumentModel::getModelChildrenNames(); 
		foreach (explode(',', $modelList) as $modelItem)
		{
			$modelItem = trim($modelItem);
			if (strlen($modelItem) > 0 && $modelItem[0] == '[')
			{
				$modelName = str_replace(array('[', ']'), '', $modelItem);
				try 
				{
					$models[$modelName] = true;
					if (isset($modelsChildren[$modelName]))
					{
						foreach ($modelsChildren[$modelName] as $childModelName)
						{
							$models[$childModelName] = true;
						}
					}
					
					continue;
				}
				catch (\Exception $e)
				{
					\Change\Application\LoggingManager::getInstance()->exception($e);
				}
			}
			else if (strlen($modelItem) > 0 && $modelItem[0] == '!')
			{
				$unsetType = substr($modelItem, 1);
				if (isset($models[$unsetType]))
				{
					unset($models[$unsetType]);
				}
				continue;
			} 
			else if (strlen($modelItem) > 0)
			{
				$models[$modelItem] = true;
			}
		}
		return array_keys($models);
	}
}
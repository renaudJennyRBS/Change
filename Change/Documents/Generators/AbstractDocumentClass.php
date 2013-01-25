<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\AbstractDocumentClass
 */
class AbstractDocumentClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $model->getShortAbstractDocumentClassName() . '.php';
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$this->compiler = $compiler;
		$code = '<'. '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		if (!$model->getInject())
		{
			$code .= '/**
 * @name ' . $model->getDocumentClassName() . '
 * @method ' . $model->getServiceClassName() . ' getDocumentService()
 * @method ' . $model->getModelClassName() . ' getDocumentModel()
 */'.PHP_EOL;	
		}
		
		$extendModel = $model->getExtendModel();
		$extend = $extendModel ? $extendModel->getDocumentClassName() : '\Change\Documents\AbstractDocument';
		
		$interfaces = array();
		
		// implements , 
		if ($model->getLocalized()) {$interfaces[] = '\Change\Documents\Interfaces\Localizable';}
		if ($model->getEditable()) {$interfaces[] = '\Change\Documents\Interfaces\Editable';}
		if ($model->getPublishable()) {$interfaces[] = '\Change\Documents\Interfaces\Publishable';}
		if ($model->getUseVersion()) {$interfaces[] = '\Change\Documents\Interfaces\Versionable';}
		
		if (count($interfaces))
		{
			$extend .= ' implements ' . implode(', ', $interfaces);
		}
		
		$code .= 'abstract class ' . $model->getShortAbstractDocumentClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$properties = $this->getMemberProperties($model);
		
		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);

			foreach ($properties as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getType() === 'DocumentArray')
				{
					$code .= $this->getPropertyDocumentArrayAccessors($model, $property);
				}
				elseif ($property->getType() === 'Document')
				{
					$code .= $this->getPropertyDocumentAccessors($model, $property);
				}
				elseif ($property->getLocalized())
				{
					$code .= $this->getPropertyLocalizedAccessors($model, $property);
				}
				else
				{
					$code .= $this->getPropertyAccessors($model, $property);
				}
			}
		}
		
		if ($model->getLocalized())
		{
			$code .= $this->getLocalizableInterface($model);
		}

		$code .= '}'. PHP_EOL;
		$this->compiler = null;
		return $code;
	}
	
	
	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function escapePHPValue($value, $removeSpace = true)
	{
		if ($removeSpace)
		{
			return str_replace(array(PHP_EOL, ' ', "\t"), '', var_export($value, true));
		}
		return var_export($value, true);
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getLocalizableInterface($model)
	{
		$class = $model->getDocumentI18nClassName();
		$code = '
	/**
	 * @var '.$class.'[]
	 */
	protected $i18nPartArray = array();
	
	/**
	 * @var string[]
	 */
	protected $LCIDArray;
	
	/**
	 * @param string[]
	 */
	public function getLCIDArray()
	{
		if ($this->LCIDArray === null)
		{
			$this->LCIDArray = $this->getDocumentManager()->getI18nDocumentLCIDArray($this);
		}
		foreach ($this->i18nPartArray as $LCID => $i18nPart)
		{
			if (!in_array($LCID, $this->LCIDArray) && $i18nPart->getPersistentState() === \Change\Documents\DocumentManager::STATE_LOADED)
			{
				$this->LCIDArray[] = $LCID;
			}
		}
		return $this->LCIDArray;
	}
	 	
	/**
	 * @param string $LCID
	 * @return '.$class.'|null
	 */
	public function getI18nPart($LCID)
	{
		if (isset($this->i18nPartArray[$LCID]))
		{
			return $this->i18nPartArray[$LCID];
		}
		$LCIDArray = $this->getLCIDArray();
		if (in_array($LCID, $LCIDArray))
		{
			$this->i18nPartArray[$LCID] = $this->getDocumentManager()->getI18nDocumentInstanceByDocument($this, $LCID);
			return $this->i18nPartArray[$LCID];
		}
	 	return null;
	}
	 	
	/**
	 * @param '.$class.'|null $i18nPart
	 */
	public function deleteI18nPart($i18nPart = null)
	{
		if ($i18nPart === null)
		{
			foreach ($this->i18nPartArray as $LCID => $i18nPart)
			{
				$i18nPart->setPersistentState(\Change\Documents\DocumentManager::STATE_DELETED);
			}
	 		$this->LCIDArray = array();
		}
		elseif ($i18nPart instanceof '.$class.')
		{
			$LCID = $i18nPart->getLCID();
			if ($this->i18nPartArray[$LCID] === $i18nPart)
			{
				$i18nPart->setPersistentState(\Change\Documents\DocumentManager::STATE_DELETED);
				if ($this->LCIDArray !== null)
				{
					$this->LCIDArray = array_values(array_diff($this->LCIDArray, array($LCID)));
				}
			}
		}
	}
	 		 				
	/**
	 * @return '.$class.'
	 */
	public function getCurrentI18nPart()
	{
	 	$LCID = $this->getDocumentManager()->getLCID();
	 	if (!isset($this->i18nPartArray[$LCID]))
	 	{
	 		$this->i18nPartArray[$LCID] = $this->getDocumentManager()->getI18nDocumentInstanceByDocument($this, $LCID);
	 	}
	 	return $this->i18nPartArray[$LCID];
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->getCurrentI18nPart()->isDeleted();
	}			

	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->getCurrentI18nPart()->isNew();
	}
			
	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		return parent::hasModifiedProperties() || $this->getCurrentI18nPart()->hasModifiedProperties();
	}
			
	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return parent::isPropertyModified($propertyName) || $this->getCurrentI18nPart()->isPropertyModified($propertyName);
	}
			
	/**
	 * @api
	 * @return string[]
	 */
	public function getModifiedPropertyNames()
	{
		return array_merge(parent::getModifiedPropertyNames(), $this->getCurrentI18nPart()->getModifiedPropertyNames());
	}
			
	/**
	 * @api
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getOldPropertyValue($propertyName)
	{
		$i18nPart = $this->getCurrentI18nPart();
		if ($i18nPart->isPropertyModified($propertyName))
		{
			return $i18nPart->getOldPropertyValue($propertyName);
		}
		return parent::getOldPropertyValue($propertyName);
	}
			
	/**
	 * @api
	 * @return array<string => mixed>
	 */
	public function getOldPropertyValues()
	{
		return array_merge(parent::getOldPropertyValues(), $this->getCurrentI18nPart()->getOldPropertyValues());
	}'. PHP_EOL;
		
		return $code;
	}
	

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Property[]
	 */
	protected function getMemberProperties($model)
	{	
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if (!$property->getParent())
			{
				$properties[$property->getName()] = $property;
			}
		}
		return $properties;
	}
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getMembers($model, $properties)
	{
		$resetProperties = array();
		if ($model->getLocalized())
		{
			$resetProperties[] = '		$this->getCurrentI18nPart()->reset($this->documentModel);';
		}
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized())
			{
				continue;
			}
			$resetProperties[] = '		$this->'.$property->getName().' = null;';
			$code .= '
	/**
	 * @var '.$this->getCommentaryMemberType($property).'
	 */	
	private $'.$property->getName().';'. PHP_EOL;
		}
		
		$code .= '		
	/**
	 * @api
	 */
	public function reset()
	{
		parent::reset();' . PHP_EOL. implode(PHP_EOL, $resetProperties).'
	}'.PHP_EOL;

		return $code;
	}
			
	/**
	 * @return string
	 */
	public function getCommentaryType($property)
	{
		switch ($property->getComputedType())
		{
			case 'Boolean' :
				return 'boolean';
			case 'Float' :
			case 'Decimal' :
				return 'float';
			case 'Integer' :
			case 'DocumentId' :
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			case 'Document' :
			case 'DocumentArray' :
				if ($property->getDocumentType() === null)
				{
					return '\Change\Documents\AbstractDocument';
				}
				else
				{
					return $this->compiler->getModelByName($property->getDocumentType())->getDocumentClassName();
				}
			default:
				return 'string';
		}
	}
	
	
	/**
	 * @return string
	 */
	public function getCommentaryMemberType($property)
	{
		switch ($property->getType())
		{
			case 'Boolean' :
				return 'boolean';
			case 'Float' :
			case 'Decimal' :
				return 'float';
			case 'Integer' :
			case 'DocumentId' :
			case 'Document' :
			case 'DocumentArray' :
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			default:
				return 'string';
		}
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @param string $varName
	 * @return string
	 */	
	protected function buildValConverter($property, $varName)
	{
		if ($property->getType() === 'DateTime')
		{
			return $varName.' = is_string('.$varName.') ? new \DateTime('.$varName.', new \DateTimeZone(\'UTC\')): (('.$varName.' instanceof \DateTime) ? '.$varName.' : null)';
		}
		elseif ($property->getType() === 'Date')
		{
			return $varName.' = is_string('.$varName.') ? new \DateTime('.$varName.', new \DateTimeZone(\'UTC\')) : '.$varName.'; '.$varName.' = ('.$varName.' instanceof \DateTime) ? \DateTime::createFromFormat(\'Y-m-d\', '.$varName.'->format(\'Y-m-d\'), new \DateTimeZone(\'UTC\'))->setTime(0, 0) : null';
		}
		elseif ($property->getType() === 'Boolean')
		{
			return $varName.' = ('.$varName.' === null) ? '.$varName.' : (bool)'.$varName.'';
		}
		elseif ($property->getType() === 'Integer')
		{
			return $varName.' = ('.$varName.' === null) ? '.$varName.' : intval('.$varName.')';
		}
		elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
		{
			return $varName.' = ('.$varName.' === null) ? '.$varName.' : floatval('.$varName.')';
		}
		elseif ($property->getType() === 'DocumentId')
		{
			return $varName.' = ('.$varName.' === null) ? '.$varName.' : ('.$varName.' instanceof \Change\Documents\AbstractDocument) ? '.$varName.'->getId() : intval('.$varName.') > 0 ? intval('.$varName.') : null';
		}
		elseif ($property->getType() === 'JSON')
		{
			return $varName.' = ('.$varName.' === null || is_string('.$varName.')) ? '.$varName.' : json_encode('.$varName.')';
		}
		elseif ($property->getType() === 'Object')
		{
			return $varName.' = ('.$varName.' === null || is_string('.$varName.')) ? '.$varName.' : serialize('.$varName.')';
		}
		elseif ($property->getType() === 'Document' || $property->getType() === 'DocumentArray')
		{
			return $varName.' = '.$varName.' === null || !('.$varName.' instanceof \Change\Documents\AbstractDocument)) ? null : '.$varName.'->getId()';
		}
		else
		{
			return $varName.' = '.$varName.' === null ? '.$varName.' : strval('.$varName.')';
		}
	}
	
	/**
	 * @param string $oldVarName
	 * @param string $newVarName
	 * @param string $type
	 * @return string
	 */
	protected function buildEqualsProperty($oldVarName, $newVarName, $type)
	{
		if ($type === 'Float' || $type === 'Decimal')
		{
			return 'abs(floatval('.$oldVarName.') - '.$newVarName.') <= 0.0001';
		}
		elseif ($type === 'Date' || $type === 'DateTime')
		{
			return $oldVarName . ' == ' . $newVarName;
		}
		else
		{
			return $oldVarName . ' === ' . $newVarName;
		}
	}
	
	/**
	 * @param string $oldVarName
	 * @param string $newVarName
	 * @param string $type
	 * @return string
	 */
	protected function buildNotEqualsProperty($oldVarName, $newVarName, $type)
	{
		if ($type === 'Float' || $type === 'Decimal')
		{
			return 'abs(floatval('.$oldVarName.') - '.$newVarName.') > 0.0001';
		}
		elseif ($type === 'Date' || $type === 'DateTime')
		{
			return $oldVarName . ' != ' . $newVarName;
		}
		else
		{
			return $oldVarName . ' !== ' . $newVarName;
		}
	}
	

		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */	
	protected function getPropertyAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$'.$name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
		$this->checkLoaded();
		return '.$mn.';
	}
	
	/**
	 * @return '.$ct.'|null
	 */
	public function get'.$un.'OldValue()
	{
		return $this->getOldPropertyValue('.$en.');
	}
	
	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function set'.$un.'('.$var.')
	{
		' . $this->buildValConverter($property, $var) . ';
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			'.$mn.' = '.$var.';
			return;
		}
		$this->checkLoaded();
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			'.$mn.' = '.$var.';
		}
		elseif ('. $this->buildNotEqualsProperty($mn, $var, $property->getType()).')
		{
			if ($this->isPropertyModified('.$en.'))
			{
				$loadedVal = $this->getOldPropertyValue('.$en.');
				if ('. $this->buildEqualsProperty('$loadedVal', $var, $property->getType()).')
				{
					$this->removeOldPropertyValue('.$en.');
				}
			}
			else
			{
				$this->setOldPropertyValue('.$en.', '.$mn.');
			}
			'.$mn.' = '.$var.';
			$this->propertyChanged('.$en.');
		}
	}'.PHP_EOL;
		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyLocalizedAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$'.$name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
		$i18nPart = $this->getCurrentI18nPart();
		return $i18nPart->get'.$un.'();
	}
			
	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function set'.$un.'('.$var.')
	{
		$i18nPart = $this->getCurrentI18nPart();
		if ($i18nPart->set'.$un.'('.$var.'))
		{
			$this->propertyChanged('.$en.');	
		}
	} 
			
	/**
	 * @return '.$ct.'|null
	 */
	public function get'.$un.'OldValue()
	{
		return $this->getCurrentI18nPart()->get'.$un.'OldValue();
	}'.PHP_EOL;
		
		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyExtraGetters($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$'.$name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$modelGetter =  'getProperty';
	
		if ($property->getType() === 'XML')
		{
			$code .= '
	/**
	 * @return \DOMDocument
	 */
	public function get'.$un.'DOMDocument()
	{
		$document = new \DOMDocument("1.0", "UTF-8");
		if ($this->get'.$un.'() !== null) {$document->loadXML($this->get'.$un.'());}
		return $document;
	}
		
	/**
	 * @param \DOMDocument $document
	 */
	public function set'.$un.'DOMDocument($document)
	{
		 $this->set'.$un.'($document && $document->documentElement ? $document->saveXML() : null);
	}'.PHP_EOL;
		}	
		elseif ($property->getType() === 'JSON')
		{
			$code .= '	
	/**
	 * @return array
	 */
	public function getDecoded'.$un.'()
	{
		'.$var.' = $this->get'.$un.'();
		return '.$var.' === null ? '.$var.' : json_decode('.$var.', true);
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'Object')
		{
			$code .= '	
	/**
	 * @return mixed
	 */
	public function getDecoded'.$un.'()
	{
		'.$var.' = $this->get'.$un.'();
		return '.$var.' === null ? '.$var.' : unserialize('.$var.');
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'DocumentId')
		{
			$code .= '
	/**
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function get'.$un.'Instance()
	{
		return $this->getDocumentManager()->getDocumentInstance($this->get'.$un.'());
	}'.PHP_EOL;
		}
		return $code;
	}
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$'.$name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		
		$code .= '	
	/**
	 * @return integer
	 */
	public function get'.$un.'OldValueId()
	{
		return $this->getOldPropertyValue('.$en.');
	}
	
	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function set'.$un.'('.$var.' = null)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			'.$mn.' = '.$var.' === null ? null : intval('.$var.');
			return;
		}
		if ('.$var.' !== null && !('.$var.' instanceof '.$ct.'))
		{
			throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an '.$ct.'\');
		}
		$this->checkLoaded();
		$newId = ('.$var.' instanceof \Change\Documents\AbstractDocument) ? $this->getDocumentManager()->initializeRelationDocumentId('.$var.') : null;
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			'.$mn.' = $newId;
		}
		elseif ('.$mn.' !== $newId)
		{
			if ($this->isPropertyModified('.$en.'))
			{
				$loadedVal = $this->getOldPropertyValue('.$en.');
				if ($loadedVal !== $newId)
				{
					$this->removeOldPropertyValue('.$en.');
				}
			}
			else
			{
				$this->setOldPropertyValue('.$en.', '.$mn.');
			}
			'.$mn.' = $newId;
			$this->propertyChanged('.$en.');
		}
	}

	/**
	 * @return integer
	 */
	public function get'.$un.'Id()
	{
		$this->checkLoaded();
		return '.$mn.';
	}
	
	/**
	 * @return '.$ct.'|null
	 */
	public function get'.$un.'()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return '.$mn.';
		}
		$this->checkLoaded();
		return ('.$mn.') ? $this->getDocumentManager()->getRelationDocument('.$mn.') : null;
	}' . PHP_EOL;
		
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentArrayAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$'.$name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return integer[]
	 */
	public function get'.$un.'OldValueIds()
	{
		$result = $this->getOldPropertyValue('.$en.');
		return (is_array($result)) ? $result : array();
	}
	
	protected function checkLoaded'.$un.'()
	{
		$this->checkLoaded();
		if (!is_array('.$mn.'))
		{
			if ('.$mn.')
			{
				'.$mn.' = $this->getDocumentManager()->getPropertyDocumentIds($this, '.$en.');
			}
			else
			{
				'.$mn.' = array();
			}
		}
	}

	/**
	 * @return '.$ct.'[]
	 */
	public function get'.$un.'()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return is_array('.$mn.') ? count('.$mn.') : '.$mn.';
		}
		$this->checkLoaded'.$un.'();
		$documents = array(); 
		$dm = $this->getDocumentManager();
		array_walk('.$mn.', function ($documentId, $index) use (&$documents, $dm) {
			$documents[] = $dm->getRelationDocument($documentId);
		});
		return $documents;
	}

	/**
	 * @param '.$ct.'[] $newValueArray
	 */
	public function set'.$un.'($newValueArray)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			'.$mn.' = intval($newValueArray);
			return;
		}
		if (!is_array($newValueArray))
		{
			throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an array\');
		}
		$this->checkLoaded'.$un.'();
		$newValueIds = array(); 
		$dm = $this->getDocumentManager();
		array_walk($newValueArray, function ($newValue, $index) use (&$newValueIds, $dm) {
			if ($newValue instanceof '.$ct.')
			{
				$newValueIds[] = $dm->initializeRelationDocumentId($newValue);
			}
			else
			{
				throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an '.$ct.'[]\');
			}
		});
			
		$this->setInternal'.$un.'Ids($newValueIds);
	}
			
			
	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function add'.$un.'('.$ct.' '.$var.')
	{
		$this->set'.$un.'AtIndex('.$var.', -1);
	}	

	/**
	 * @param '.$ct.' '.$var.'
	 * @param integer $index
	 */
	public function set'.$un.'AtIndex('.$ct.' '.$var.', $index = 0)
	{
		$this->checkLoaded'.$un.'();
		$newId = $this->getDocumentManager()->initializeRelationDocumentId('.$var.');		
		if (!in_array($newId, '.$mn.'))
		{
			$newValueIds = '.$mn.'; 
			$index = intval($index);
			if ($index < 0 || $index > count($newValueIds))
			{
				$index = count($newValueIds);
			}
			$newValueIds[$index] = $newId;		
			$this->setInternal'.$un.'Ids($newValueIds);
		}	
	}

	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function remove'.$un.'('.$ct.' '.$var.')
	{
		$index = $this->getIndexof'.$un.'('.$var.');
		if ($index !== -1)
		{
			$this->remove'.$un.'ByIndex($index);
		}
	}

	/**
	 * @param integer $index
	 */
	public function remove'.$un.'ByIndex($index)
	{
		$this->checkLoaded'.$un.'();
		if (isset('.$mn.'[$index]))
		{
			$newValueIds = '.$mn.';
			unset($newValueIds[$index]);	
			$this->setInternal'.$un.'Ids($newValueIds);
		}
	}

	public function removeAll'.$un.'()
	{
		$this->checkLoaded'.$un.'();		
		$this->setInternal'.$un.'Ids(array());
	}

	/**
	 * @param integer[] $newValueIds
	 */
	protected function setInternal'.$un.'Ids(array $newValueIds)
	{
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			'.$mn.' = $newValueIds;
		}
		elseif ('.$mn.' != $newValueIds)
		{
			if ($this->isPropertyModified('.$en.'))
			{
				$loadedVal = $this->getOldPropertyValue('.$en.');
				if ($loadedVal == $newValueIds)
				{
					$this->removeOldPropertyValue('.$en.');
				}
			}
			else
			{
				$this->setOldPropertyValue('.$en.', '.$mn.');
			}
			'.$mn.' = $newValueIds;
			$this->propertyChanged('.$en.');
		}
	}

	/**
	 * @param integer $index
	 * @return '.$ct.'|null
	 */
	public function get'.$un.'ByIndex($index)
	{
		$this->checkLoaded'.$un.'();
		return isset('.$mn.'[$index]) ?  $this->getDocumentManager()->getRelationDocument('.$mn.'[$index]) : null;
	}
	
	/**
	 * @return integer[]
	 */
	public function get'.$un.'Ids()
	{
		$this->checkLoaded'.$un.'();
		return '.$mn.';
	}

	/**
	 * @param '.$ct.' '.$var.'
	 * @return integer
	 */
	public function getIndexof'.$un.'('.$ct.' '.$var.')
	{
		$this->checkLoaded'.$un.'();
		$valueId = $this->getDocumentManager()->initializeRelationDocumentId('.$var.');
		$index = array_search($valueId, '.$mn.');
		return $index !== false ? $index : -1;
	}' . PHP_EOL;
		return $code;
	}
}
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
		if ($model->getUseCorrection()) {$interfaces[] = '\Change\Documents\Interfaces\Correction';}
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
			$code .= $this->getDbProviderFunctions($model, $properties);
			$code .= $this->getValidateFunctions($model, $properties);
			
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
		
		
		
		$code .= $this->getSetDefaultValues($model);
		
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
	 * @param string $LCID
	 * @return '.$class.'|null
	 */
	public function getI18nPart($LCID)
	{
	 	return isset($this->i18nPartArray[$LCID]) ? $this->i18nPartArray[$LCID] : null;
	}
	 				
	/**
	 * @return '.$class.'
	 */
	public function getCurrentI18nPart()
	{
	 	$LCID = $this->getDocumentManager()->getLCID();
	 	$i18nPart = $this->getI18nPart($LCID);
	 	if ($i18nPart === null)
	 	{
	 		$i18nPart = $this->getDocumentManager()->getI18nDocumentInstanceByDocument($this, $LCID);
	 		$this->i18nPartArray[$LCID] = $i18nPart;
	 	}
		return $i18nPart;
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
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized())
			{
				continue;
			}	
			$code .= '	private $'.$property->getName().';'. PHP_EOL;
		}
		return $code;
	}
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getSetDefaultValues($model)
	{
		$lines = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getDefaultValue() !== null)
			{
				$lines[] = '		$this->set' . ucfirst($property->getName()) . 'Internal(' . $this->escapePHPValue($property->getDefaultPhpValue(), false). ');';
			}
		}
		if (count($lines))
		{
			$lines[] = '		parent::setDefaultValues();';
			$code = '
	/**
	 * @return void
	 */
	protected function setDefaultValues()
	{' . PHP_EOL . implode(PHP_EOL, $lines);
			$code .= '
	}' . PHP_EOL;
			
			return $code;
		}
		return '';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getDbProviderFunctions($model, $properties)
	{
		$code = '';
		$get = array();	
		$set = array();
		foreach ($properties as $property)
		{
			if ($property->getLocalized())
			{
				continue;
			}
			
			/* @var $property \Change\Documents\Generators\Property */
			$name = $property->getName();
			$get[] = '		$propertyBag['.$this->escapePHPValue($name).'] = $this->'.$name.';';
			
			
			if ($property->getType() === 'Boolean')
			{
				$sv = '(null === $propertyValue) ? null : (bool)$propertyValue'; 
			}
			elseif ($property->getType() === 'Integer' || $property->getType() === 'DocumentId')
			{
				$sv = '(null === $propertyValue) ? null : intval($propertyValue)';
			}
			elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
			{
				$sv = '(null === $propertyValue) ? null : floatval($propertyValue)';
			}
			elseif ($property->getType() === 'DocumentArray')
			{
				$sv = '(null === $propertyValue) ? 0 : intval($propertyValue)';
			}
			else
			{
				$sv = '$propertyValue';
			}
			$set[] = '				case '.$this->escapePHPValue($name).' : $this->'.$name.' = '.$sv.'; break;';
		}
				
		$code .= '
	/**
	 * @return array
	 */
	public function getDocumentProperties()
	{
		$propertyBag = parent::getDocumentProperties();' . PHP_EOL;
		$code .= implode(PHP_EOL, $get);
		$code .= '
		return $propertyBag;
	}
	
	/**
	 * @param array<String, mixed> $lang
	 * @return void
	 */
	public function setDocumentProperties($propertyBag)
	{
		parent::setDocumentProperties($propertyBag);
		foreach ($propertyBag as $propertyName => $propertyValue)
		{
			switch ($propertyName)
			{' . PHP_EOL;
		$code .= implode(PHP_EOL, $set);		
		$code .= '
			}
		}
	}' . PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getValidateFunctions($model, $properties)
	{	
		$code = '';
		$validates = array();
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$name = $property->getName();
			$validates[] = '		$this->is'.ucfirst($name).'Valid();';
			$code .= $this->generatePropertyValidateFunction($model, $property);
		}
		$code .= '
	/**
	 * @return void
	 */
	public function validateProperties()
	{
		parent::validateProperties();' . PHP_EOL;
		$code .= implode(PHP_EOL, $validates);
		$code .= '	
	}' . PHP_EOL;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function generatePropertyValidateFunction($model, $property)
	{
		$name = $property->getName();
		$eName = $this->escapePHPValue($name);
		$uName = ucfirst($name);
		if ($property->getType() === 'DocumentArray')
		{
			$code = '
	/**
	 * @return boolean
	 */
	public function is'.$uName.'Valid()
	{
		if ($this->persistentStateIsNew() || $this->isPropertyModified('.$eName.'))
		{
			$prop = $this->getDocumentModel()->getProperty('.$eName.');
			$value = $this->get'.$uName.'Count();
			if ($value === 0) {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('.$eName.', new \Change\I18n\PreparedKey(\'f.constraints.isempty\', array(\'ucf\')));
				return false;
			}
			elseif ($prop->getMaxOccurs() > 1 && value > $prop->getMaxOccurs()) {
				$args = array(\'maxOccurs\' => $prop->getMaxOccurs());
				$this->addPropertyErrors('.$eName.', new \Change\I18n\PreparedKey(\'f.constraints.maxoccurs\', array(\'ucf\'), array($args)));
				return false;
			}
			elseif ($prop->getMinOccurs() > 1 && value < $prop->getMinOccurs()) {
				$args = array(\'minOccurs\' => $prop->getMinOccurs());
				$this->addPropertyErrors('.$eName.', new \Change\I18n\PreparedKey(\'f.constraints.minoccurs\', array(\'ucf\'), array($args)));
				return false;
			}
		}
		return true;
	}' . PHP_EOL;
			return $code;
		}
		
		$code = '
	/**
	 * @return boolean
	 */
	public function is'.$uName.'Valid()
	{
		if ($this->persistentStateIsNew() || $this->isPropertyModified('.$eName.'))
		{
			$prop = $this->getDocumentModel()->getProperty('.$eName.');
			$value = $this->get'.$uName.'();
			if ($value === null || $value === \'\') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('.$eName.', new \Change\I18n\PreparedKey(\'f.constraints.isempty\', array(\'ucf\')));
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array(\'documentId\' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('.$eName.', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}' . PHP_EOL;
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
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */	
	protected function buildValConverter($property, $var)
	{
		if ($property->getType() === 'DateTime' || $property->getType() === 'Date')
		{
			return ''.$var.' = ('.$var.' === null) ? '.$var.' : ('.$var.' instanceof \date_Calendar) ? \date_Formatter::format('.$var.', \date_Formatter::SQL_DATE_FORMAT) : is_long('.$var.') ? date(\date_Formatter::SQL_DATE_FORMAT, '.$var.') : '.$var.'';
		}
		elseif ($property->getType() === 'Boolean')
		{
			return ''.$var.' = ('.$var.' === null) ? '.$var.' : (bool)'.$var.'';
		}
		elseif ($property->getType() === 'Integer')
		{
			return ''.$var.' = ('.$var.' === null) ? '.$var.' : intval('.$var.')';
		}
		elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
		{
			return ''.$var.' = ('.$var.' === null) ? '.$var.' : floatval('.$var.')';
		}
		elseif ($property->getType() === 'DocumentId')
		{
			return ''.$var.' = ('.$var.' === null) ? '.$var.' : ('.$var.' instanceof \Change\Documents\AbstractDocument) ? '.$var.'->getId() : intval('.$var.') > 0 ? intval('.$var.') : null';
		}
		elseif ($property->getType() === 'JSON')
		{
			return ''.$var.' = ('.$var.' === null || is_string('.$var.')) ? '.$var.' : \JsonService::getInstance()->encode('.$var.')';
		}
		elseif ($property->getType() === 'Object')
		{
			return ''.$var.' = ('.$var.' === null || is_string('.$var.')) ? '.$var.' : serialize('.$var.')';
		}
		elseif ($property->getType() === 'Document' || $property->getType() === 'DocumentArray')
		{
			return ''.$var.' = '.$var.' === null || !('.$var.' instanceof \Change\Documents\AbstractDocument)) ? null : '.$var.'->getId()';
		}
		else
		{
			return ''.$var.' = '.$var.' === null ? '.$var.' : strval('.$var.')';
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
	 * @return '.$ct.'|NULL
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
		$this->checkLoaded();'.PHP_EOL;
		$code .= '		' . $this->buildValConverter($property, $var) . ';'.PHP_EOL;
		$code .= '		$this->set'.$un.'Internal('.$var.');
	}'.PHP_EOL;
		
		$code .= '
	protected function set'.$un.'Internal('.$var.')
	{
		$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';'.PHP_EOL;
		if ($property->getType() === 'Float' || $property->getType() === 'Decimal')
		{
			$code .= '		$modified = (abs(floatval($oldVal) - '.$var.') > 0.0001);'.PHP_EOL;
		}
		else
		{
			$code .= '		$modified = ($oldVal !== '.$var.');'.PHP_EOL;
		}
		
		$code .= '		if ($modified)
		{
			$this->setOldPropertyValue('.$en.', $oldVal);
			'.$mn.' = '.$var.';
		}
		elseif($this->isPropertyModified('.$en.'))
		{
			$this->removeOldPropertyValue('.$en.');
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
		$i18nPart->set'.$un.'('.$var.');
	} 
			
	/**
	 * @return '.$ct.'|NULL
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
	 * @return \Change\Documents\AbstractDocument|NULL
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
	public function set'.$un.'('.$var.')
	{
		$this->checkLoaded();
		$newId = ('.$var.' instanceof \Change\Documents\AbstractDocument) ? $this->getDocumentManager()->initializeRelationDocumentId('.$var.') : null;
		$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';
		if ($oldVal !== $newId)
		{
			$this->setOldPropertyValue('.$en.', $oldVal);
			'.$mn.' = $newId;
		}
		elseif ($this->isPropertyModified('.$en.'))
		{
			$this->removeOldPropertyValue('.$en.');
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
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
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
			if ($this->getPersistentState() != static::PERSISTENTSTATE_NEW)
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
	 * @param '.$ct.' '.$var.'
	 */
	public function add'.$un.'('.$var.')
	{
		$this->set'.$un.'(-1, '.$var.');
	}	

	/**
	 * @param integer $index
	 * @param '.$ct.' '.$var.'
	 */
	public function set'.$un.'($index, '.$var.')
	{
		if ('.$var.' instanceof \Change\Documents\AbstractDocument)
		{
			$this->checkLoaded'.$un.'();
			$newId = $this->getDocumentManager()->initializeRelationDocumentId('.$var.');			
			if (!in_array($newId, '.$mn.'))
			{
				$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';
				$index = intval($index);
				if ($index < 0 || $index > count('.$mn.'))
				{
					$index = count('.$mn.');
				}
				'.$mn.'[$index] = $newId;		
				if ($oldVal != '.$mn.')
				{
					$this->setOldPropertyValue('.$en.', $oldVal);
				}
				elseif ($this->isPropertyModified('.$en.'))
				{
					$this->removeOldPropertyValue('.$en.');
				}
			}	
		}
		else
		{
			throw new \Exception(__METHOD__. \': Invalid document\');
		}
	}

	/**
	 * @param '.$ct.'[] $newValueArray
	 */
	public function set'.$un.'Array($newValueArray)
	{
		if (is_array($newValueArray))
		{
			$this->checkLoaded'.$un.'();
			$newValueIds = array(); 
			$dm = $this->getDocumentManager();
			array_walk($newValueArray, function ($newValue, $index) use (&$newValueIds, $dm) {
				if ($newValue instanceof \Change\Documents\AbstractDocument)
				{
					$newValueIds[] = $dm->initializeRelationDocumentId($newValue);
				}
				else
				{
					throw new \Exception(__METHOD__. \': Invalid document\');
				}
			});
			$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';	
			if ($oldVal != $newValueIds)
			{
				$this->setOldPropertyValue('.$en.', $oldVal);
				'.$mn.' = $newValueIds;
			}
			elseif ($this->isPropertyModified('.$en.'))
			{
				$this->removeOldPropertyValue('.$en.');
			}
		}
		else
		{
			throw new \Exception(\'Invalid array\');
		}
	}

	/**
	 * @param '.$ct.' '.$var.'
	 */
	public function remove'.$un.'('.$var.')
	{
		$this->checkLoaded'.$un.'();
		if ('.$var.' instanceof \Change\Documents\AbstractDocument)
		{
			$valueId = $this->getDocumentManager()->initializeRelationDocumentId('.$var.');
			if (in_array($valueId, '.$mn.'))
			{
				$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';
				unset('.$mn.'[$index]);
				if ($oldVal != '.$mn.')
				{
					$this->setOldPropertyValue('.$en.', $oldVal);
				}
				elseif ($this->isPropertyModified('.$en.'))
				{
					$this->removeOldPropertyValue('.$en.');
				}
			}
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
			$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';
			unset('.$mn.'[$index]);
			if ($oldVal != '.$mn.')
			{
				$this->setOldPropertyValue('.$en.', $oldVal);
			}
			elseif ($this->isPropertyModified('.$en.'))
			{
				$this->removeOldPropertyValue('.$en.');
			}
		}
	}

	public function removeAll'.$un.'()
	{
		$this->checkLoaded'.$un.'();
		if (count('.$mn.'))
		{
			$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';
			'.$mn.' = array();
			if ($oldVal != '.$mn.')
			{
				$this->setOldPropertyValue('.$en.', $oldVal);
			}
			elseif ($this->isPropertyModified('.$en.'))
			{
				$this->removeOldPropertyValue('.$en.');
			}
		}
	}

	/**
	 * @param integer $index
	 * @return '.$ct.'
	 */
	public function get'.$un.'($index)
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
	 * @return '.$ct.'[]
	 */
	public function get'.$un.'Array()
	{
		$this->checkLoaded'.$un.'();
		$documents = array(); 
		$dm = $this->getDocumentManager();
		array_walk('.$mn.', function ($documentId, $index) use (&$documents, $dm) {
			$documents[] = $dm->getRelationDocument($documentId);
		});
		return $documents;
	}

	/**
	 * @param '.$ct.' '.$var.'
	 * @return integer
	 */
	public function getIndexof'.$un.'('.$var.')
	{
		if ('.$var.' instanceof \Change\Documents\AbstractDocument)
		{
			$this->checkLoaded'.$un.'();
			$valueId = $this->getDocumentManager()->initializeRelationDocumentId('.$var.');
			$index = array_search($valueId, '.$mn.');
			return $index !== false ? $index : -1;
		}
		throw new \Exception(__METHOD__. \': Invalid document\');
	}' . PHP_EOL;
		return $code;
	}
}
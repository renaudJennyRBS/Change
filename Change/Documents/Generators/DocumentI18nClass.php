<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\DocumentI18nClass
 */
class DocumentI18nClass
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
		$nsParts[] = $model->getShortDocumentI18nClassName() . '.php';
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
		if (!$model->checkLocalized())
		{
			return null;
		}
		$this->compiler = $compiler;
		$code = '<'. '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		
		if ($model->getExtend() !== null)
		{
			$extend = $model->getExtendModel()->getDocumentI18nClassName();
		}
		else
		{
			$extend = '\Change\Documents\AbstractI18nDocument';
		}
		
		$code .= 'class ' . $model->getShortDocumentI18nClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$properties = $this->getI18nProperties($model);
		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);
			$code .= $this->getDbProviderFunctions($model, $properties);
			$code .= $this->getMembersAccessors($model, $properties);
		}
		$code .= $this->getSetDefaultValues($model);
				
		if (isset($properties['LCID']))
		{
			$code .= ' 
	/**
	 * @return string[]
	 */
	public function __sleep()
	{
		return array_merge(parent::__sleep(), array("\0".__CLASS__."\0LCID"));
	}'.PHP_EOL;
		}
		
		$code .= '}'. PHP_EOL;		
		$this->compiler = null;
		return $code;		
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */
	protected function getI18nProperties($model)
	{		
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getParent() == null && $property->getLocalized())
			{
				$properties[$property->getName()] = $property;
			}
		}
		return $properties;
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
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */	
	protected function getMembers($model, $properties)
	{
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$code .= '	private $'.$property->getName().';'. PHP_EOL;
		}
		return $code;
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
	
	protected function getMembersAccessors($model, $properties)
	{
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$code .= $this->getPropertyAccessors($model, $property);
		}
		return $code;
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
	{'.PHP_EOL;
		$code .= '		' . $this->buildValConverter($property, $var) . ';'.PHP_EOL;
		$code .= '		$oldVal = $this->isPropertyModified('.$en.') ? $this->getOldPropertyValue('.$en.') : '.$mn.';'.PHP_EOL;
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
		elseif ($this->isPropertyModified('.$en.'))
		{
			$this->removeOldPropertyValue('.$en.');
		}
	}'.PHP_EOL;		
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @param string $var
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
	 * @return string
	 */
	protected function getSetDefaultValues($model)
	{
		$affects = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$affects[] = '		$this->set' . ucfirst($property->getName()) . '(' . $this->escapePHPValue($property->getDefaultPhpValue(), false). ');';
			}
		}
	
		if (count($affects) === 0)
		{
			return null;
		}
	
		$code = '
	/**
	 * @return void
	 */
	public function setDefaultValues()
	{'. PHP_EOL;
		$code .= implode(PHP_EOL, $affects). PHP_EOL;
		$code .= '		parent::setDefaultValues();
	}'. PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $property
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
}
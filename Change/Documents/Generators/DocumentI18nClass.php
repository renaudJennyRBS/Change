<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\DocumentI18nClass
 * @api
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
			$code .= $this->getMembersAccessors($model, $properties);
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
		$resetProperties = array();
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getName() !== 'LCID')
			{
				$resetProperties[] = '		$this->'.$property->getName().' = null;';
			}
			$code .= '
	/**
	 * @var '.$this->getCommentaryMemberType($property).'
	 */	
	private $'.$property->getName().';'. PHP_EOL;
		}
		
		$code .= '
	/**
	 * @api
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function reset(\Change\Documents\AbstractModel $documentModel)
	{
		parent::reset($documentModel);' . PHP_EOL. implode(PHP_EOL, $resetProperties).'
	}'.PHP_EOL;
		
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
	 * @return boolean
	 */
	public function set'.$un.'('.$var.')
	{
		' . $this->buildValConverter($property, $var) . ';
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
			return true;
		}
		return false;
	}'.PHP_EOL;		
		return $code;
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
}
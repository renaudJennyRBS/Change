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
		$nsParts[] = $this->getClassName($model) . '.php';
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
		if (!$model->getCmpLocalized() && !$model->getLocalized())
		{
			return null;
		}
		$this->compiler = $compiler;
		$code = '<'. '?php' . PHP_EOL . 'namespace Compilation\\' . $model->getNameSpace() . ';' . PHP_EOL;
		$code .= 'class ' . $this->getClassName($model) . ' extends ' . $this->getParentClassName($model) . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$properties = $this->getI18nProperties($model);
		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);
			$code .= $this->getSetDocumentProperties($model, $properties);
			$code .= $this->getGetDocumentProperties($model, $properties);		
			$code .= $this->getMembersAccessors($model, $properties);
		}
		$code .= $this->getSetDefaultValues($model);
		$code .= '}'. PHP_EOL;		
		$this->compiler = null;
		return $code;		
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */
	protected function getI18nProperties($model)
	{
		$ams = $this->compiler->getAncestors($model);
		$defined = array('label' => true);
		foreach ($ams as $pm)
		{
			/* @var $pm \Change\Documents\Generators\Model */
			foreach ($pm->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getLocalized())
				{
					$properties[$property->getName()] = true;
				}
			}
		}
		
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized() && !isset($defined[$property->getName()]))
			{
				$properties[$property->getName()] = $property;
			}
		}
		return $properties;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */	
	protected function getI18nPropertyNames($model)
	{
		$properties = array();
		if ($model)
		{
			if ($model->getExtend())
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getLocalized())
				{
					$properties[$property->getName()] = $property->getName();
				}
			}
		}
		return array();
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $className
	 * @return string
	 */
	protected function addNameSpace($model, $className)
	{
		return '\Compilation\\' . $model->getNameSpace() . '\\' . $className;
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
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getClassName($model, $withNameSpace = false)
	{
		$cn = ucfirst($model->getDocumentName()) . 'I18n';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getParentClassName($model)
	{
		while($model->getExtend() != null)
		{
			$model = $this->compiler->getModelByFullName($model->getExtend());
			if ($model->getCmpLocalized())
			{
				return $this->getClassName($model, true);
			}
			
		}	
		return '\Change\Documents\AbstractI18nDocument';
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
			$code .= '	private $m_'.$property->getName().';'. PHP_EOL;
		}
		return $code;
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
	{
		parent::setDefaultValues();'. PHP_EOL;
		$code .= implode(PHP_EOL, $affects). PHP_EOL;
		
		$code .= '		$this->setModifiedProperties();'. PHP_EOL;
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getSetDocumentProperties($model, $properties)
	{
		$code = '
    /**
     * @internal For framework internal usage only
     * @param array<String, mixed> $propertyBag
     * @return void
     */
	public function setDocumentProperties($propertyBag)
	{
		parent::setDocumentProperties($propertyBag);
		foreach ($propertyBag as $propertyName => $propertyValue)
		{
			switch ($propertyName)
			{'. PHP_EOL;
		foreach ($properties as $property)
		{
			$pv = '$propertyValue';
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getType() === 'Boolean')
			{
				$pv = '(bool)$propertyValue';
			}
			elseif ($property->getType() === 'Integer')
			{
				$pv = '(null === $propertyValue) ? null : intval($propertyValue)';
			}
			elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
			{
				$pv = '(null === $propertyValue) ? null : floatval($propertyValue)';
			}
			$code .= '				case '.$this->escapePHPValue($property->getName()).' : $this->m_'.$property->getName().' = '.$pv.'; break;'. PHP_EOL;
		}
		
		$code .= '			}
		}
	}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getGetDocumentProperties($model, $properties)
	{
		$code = '
    /**
     * @internal For framework internal usage only
     * @param array<String, mixed> $propertyBag
     * @return void
     */
	public function getDocumentProperties($propertyBag)
	{
		$propertyBag = parent::getDocumentProperties($propertyBag);'. PHP_EOL;
		foreach ($properties as $property)
		{
			$code .= '		$propertyBag['.$this->escapePHPValue($property->getName()).'] = $this->m_'.$property->getName().';'. PHP_EOL;
		}
		$code .= '		return $propertyBag;
	}'. PHP_EOL;
		return $code;
	}
	
	protected function getMembersAccessors($model, $properties)
	{
		$code = '';
		foreach ($properties as $property)
		{
			$varName = '$'.$property->getName();
			$escapeName = $this->escapePHPValue($property->getName());
			$memberName = '$this->m_'.$property->getName();
			$commentType = $this->getCommentaryType($property);
			$accesSuffix = ucfirst($property->getName());
			$code .= '
	/**
	 * @param '.$commentType.' '.$varName.'
	 * @return boolean
	 */
	public function set'.$accesSuffix.'('.$varName.')
	{'. PHP_EOL;
			if ($property->getType() == "Float" || $property->getType() == "Decimal")
			{
				$code .= '		'.$varName.' = '.$varName.' !== null ? floatval('.$varName.') : null;
		$modified = ('.$memberName.' === null || '.$varName.' === null) ? ('.$memberName.' !== '.$varName.') : (abs('.$memberName.' - '.$varName.') > 0.0001);'. PHP_EOL;
			}
			else
			{
				$code .= '		$modified = '.$memberName.' !== '.$varName.';'. PHP_EOL;
			}
			$code .= '		if ($modified)
		{
			if (!array_key_exists('.$escapeName.', $this->modifiedProperties))
			{
				$this->modifiedProperties['.$escapeName.'] = '.$memberName.';
			}
			'.$memberName.' = '.$varName.';
			$this->m_modified = true;
			return true;
		}
		return false;
	}
				
	/**
	 * @return '.$commentType.'
	 */
	public function get'.$accesSuffix.'()
	{
		return '.$memberName.';
	}
			
	/**
	 * @return '.$commentType.'|NULL
	 */
	public final function get'.$accesSuffix.'OldValue()
	{
		return array_key_exists('.$escapeName.', $this->modifiedProperties) ? $this->modifiedProperties['.$escapeName.'] : null;
	}'. PHP_EOL;
		}
		return $code;
	}
	
	/**
	 * @return string
	 */
	public function getCommentaryType($property)
	{
		switch ($property->getType())
		{
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_BOOLEAN :
				return 'boolean';
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_FLOAT :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DECIMAL :
				return 'float';
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_INTEGER :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTID :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENT :					
				return 'integer';
			default:
				return 'string';
		}
	}	
}
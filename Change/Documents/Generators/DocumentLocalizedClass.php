<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\DocumentLocalizedClass
 * @api
 */
class DocumentLocalizedClass
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
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model,
		$compilationPath)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $model->getShortDocumentLocalizedClassName() . '.php';
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
		$code = '<' . '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;

		$parentModel = $model->getParent();
		if ($parentModel !== null)
		{
			$extend = $parentModel->getDocumentLocalizedClassName();
		}
		else
		{
			$extend = '\Change\Documents\AbstractLocalizedDocument';
		}

		$code .= 'class ' . $model->getShortDocumentLocalizedClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{' . PHP_EOL;
		$properties = $this->getLocalizedProperties($model);
		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);
			$code .= $this->getMembersAccessors($model, $properties);
		}
		$code .= '}' . PHP_EOL;
		$this->compiler = null;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Property[]
	 */
	protected function getLocalizedProperties($model)
	{
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getParent() == null && !$property->getStateless() && $property->getLocalized())
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
				$resetProperties[] = '		$this->' . $property->getName() . ' = null;';
			}
			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */	
	private $' . $property->getName() . ';' . PHP_EOL;
		}
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
			return 'abs(floatval(' . $oldVarName . ') - ' . $newVarName . ') <= 0.0001';
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
			return 'abs(floatval(' . $oldVarName . ') - ' . $newVarName . ') > 0.0001';
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
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		return ' . $mn . ';
	}
	
	/**
	 * @return ' . $ct . '|NULL
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}
	
	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return boolean
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
		}
		elseif (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if (' . $this->buildEqualsProperty('$loadedVal', $var, $property->getType()) . ')
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = ' . $var . ';
			return true;
		}
		return false;
	}' . PHP_EOL;
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
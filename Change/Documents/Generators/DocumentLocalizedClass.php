<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @param boolean $removeSpace
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
	 * @param \Change\Documents\Generators\Property $property
	 * @param string $varName
	 * @return string
	 */
	protected function buildValConverter($property, $varName)
	{
		return $varName . ' = $this->convertToInternalValue(' . $varName . ', ' . $this->escapePHPValue($property->getType()) . ')';
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getMembers($model, $properties)
	{
		$modifiedProperties = array();
		$removeOldPropertiesValue = array();
		$clearModifiedProperties = array();
		$code = '';
		foreach ($properties as $property)
		{
			$memberValue = ' = null;';
			$propertyName = $property->getName();

			/* @var $property \Change\Documents\Generators\Property */
			if ($propertyName !== 'LCID')
			{
				if ($property->getType() === 'RichText')
				{
					$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': if ($this->'.$propertyName.' !== null) {$this->'.$propertyName.'->setAsDefault();} return;';
					$clearModifiedProperties[] = '$this->removeOldPropertyValue(\''.$propertyName.'\');';
				}
				else
				{
					$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': unset($this->modifiedProperties[\''.$propertyName.'\']); return;';
				}
			}
			if ($property->getType() === 'RichText')
			{
				$modifiedProperties[] = 'if ($this->'.$propertyName.' !== null && $this->'.$propertyName.'->isModified()) {$names[] = \''.$propertyName.'\';}';
			}
			elseif ($property->getType() === 'DocumentId')
			{
				$memberValue = ' = 0;';
			}

			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */
	private $' . $property->getName() . $memberValue . PHP_EOL;
		}

		if (count($modifiedProperties))
		{
			$code .= '
	/**
	 * @api
	 * @return string[]
	 */
	public function getModifiedPropertyNames()
	{
		$names =  parent::getModifiedPropertyNames();
		' . implode(PHP_EOL . '		', $modifiedProperties) . '
		return $names;
	}' . PHP_EOL;
		}

		if (count($removeOldPropertiesValue))
		{
			$code .= '
	/**
	 * @api
	 * @param string $propertyName
	 */
	public function removeOldPropertyValue($propertyName)
	{
		switch ($propertyName)
		{
			' . implode(PHP_EOL . '			', $removeOldPropertiesValue) . '
			default:
				parent::removeOldPropertyValue($propertyName);
		}
	}' . PHP_EOL;
		}

		if (count($clearModifiedProperties))
		{
			$code .= '
	protected function clearModifiedProperties()
	{
		parent::clearModifiedProperties();
		' . implode(PHP_EOL . '		', $clearModifiedProperties) . '
	}' . PHP_EOL;
		}

		return $code;
	}

	protected function getMembersAccessors($model, $properties)
	{
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getType() === 'JSON')
			{
				$code .= $this->getJSONPropertyAccessors($model, $property);
			}
			elseif ($property->getType() === 'Object')
			{
				$code .= $this->getObjectPropertyAccessors($model, $property);
			}
			elseif ($property->getType() === 'RichText')
			{
				$code .= $this->getRichTextPropertyAccessors($model, $property);
			}
			else
			{
				$code .= $this->getPropertyAccessors($model, $property);
			}
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
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code = '
	/**
	 * @param ' . $ct . '|null ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		' . $this->buildValConverter($property, $var) . ';
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
		}
		elseif (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			if (array_key_exists(' . $en . ', $this->modifiedProperties))
			{
				if (' . $this->buildEqualsProperty('$this->modifiedProperties[' . $en . ']', $var, $property->getType()) . ')
				{
					unset($this->modifiedProperties[' . $en . ']);
				}
			}
			else
			{
				$this->modifiedProperties[' . $en . '] = ' . $mn . ';
			}
			' . $mn . ' = ' . $var . ';
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}' . PHP_EOL;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getJSONPropertyAccessors($model, $property)
	{
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code = '
	/**
	 * @param '. $ct .'|null ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
			return $this;
		}
		$newString = (' . $var . ' !== null) ? \Zend\Json\Json::encode(' . $var . ') : null;
		if (' . $mn . ' !== $newString)
		{
			if (array_key_exists(' . $en . ', $this->modifiedProperties))
			{
				if ($this->modifiedProperties[' . $en . '] === $newString)
				{
					unset($this->modifiedProperties[' . $en . ']);
				}
			}
			else
			{
				$this->modifiedProperties[' . $en . '] = ' . $mn . ';
			}
			' . $mn . ' = $newString;
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		return ' . $mn . ' === null ? null : \Zend\Json\Json::decode(' . $mn . ', \Zend\Json\Json::TYPE_ARRAY);
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->getOldPropertyValue(' . $en . ');
		return ' . $var . ' === null ? ' . $var . ' : \Zend\Json\Json::decode(' . $var . ', \Zend\Json\Json::TYPE_ARRAY);
	}' . PHP_EOL;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getObjectPropertyAccessors($model, $property)
	{
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code = '
	/**
	 * @param ' . $ct . '|null ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
			return $this;
		}
		$newString = (' . $var . ' !== null) ? serialize(' . $var . ') : null;
		if (' . $mn . ' !== $newString)
		{
			if (array_key_exists(' . $en . ', $this->modifiedProperties))
			{
				if ($this->modifiedProperties[' . $en . '] === $newString)
				{
					unset($this->modifiedProperties[' . $en . ']);
				}
			}
			else
			{
				$this->modifiedProperties[' . $en . '] = ' . $mn . ';
			}
			' . $mn . ' = $newString;
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		return ' . $mn . ' === null ? null : unserialize(' . $mn . ');
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->getOldPropertyValue(' . $en . ');
		return ' . $var . ' === null ? ' . $var . ' : unserialize(' . $var . ');
	}' . PHP_EOL;
		return $code;
	}


	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getRichTextPropertyAccessors($model, $property)
	{
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code = '
	/**
	 * @param string|array|' . $ct . '|null ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_LOADING)
		{
			' . $mn . ' = new ' . $ct . '(' . $var . ');
			return $this;
		}
		if (' . $mn . ' === null)
		{
			' . $mn . ' = new ' . $ct . '();
		}

		if (is_string(' . $var . '))
		{
			' . $mn . '->fromJSONString(' . $var . ');
		}
		elseif (' . $var . ' === null || is_array(' . $var . '))
		{
			' . $mn . '->fromArray(' . $var . ');
		}
		elseif (' . $var . '  instanceof ' . $ct . ')
		{
			' . $mn . '->fromRichtextProperty(' . $var . ');
		}
		elseif (' . $var . ' !== null)
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an array, string, ' . $ct . ' or null\', 52005);
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\AbstractDocument::STATE_SAVING)
		{
			return (' . $mn . ' !== null) ? ' . $mn . '->toJSONString() : null;
		}
		if (' . $mn . ' === null)
		{
			' . $mn . ' = new ' . $ct . '();
		}
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . 'OldValue()
	{
		return new ' . $ct . '((' . $mn . ' !== null) ? ' . $mn . '->getDefaultJSONString() : null);
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
			case 'JSON' :
				return 'array';
			case 'Integer' :
			case 'DocumentId' :
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			case 'RichText' :
				return '\Change\Documents\RichtextProperty';
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
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			case 'RichText' :
				return '\Change\Documents\RichtextProperty';
			default:
				return 'string';
		}
	}
}
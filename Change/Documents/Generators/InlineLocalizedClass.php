<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\InlineLocalizedClass
 * @api
 */
class InlineLocalizedClass
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
		if (!$model->rootLocalized())
		{
			return null;
		}

		$this->compiler = $compiler;
		$code = '<' . '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;

		$extend = $model->getParentDocumentLocalizedClassName();
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
			if ($property->getParent() == null && !$property->getStateless()
				&& $property->getLocalized() && $property->getName() !== 'LCID')
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
		$unsetProperties = [];
		$fromDbData = [];
		$toDbData = [];

		$code = '';
		foreach ($properties as $property)
		{
			$propertyName = $property->getName();

			$memberValue = ' = null;';
			if ($property->getType() == 'DocumentId')
			{
				$memberValue = ' = 0;';
			}
			$unsetProperties[] = '$this->' . $propertyName . $memberValue;
			$fromDbData[] = '$this->'.$propertyName.'FromDbData($dbData);';
			$toDbData[] = '$dbData = $this->'.$propertyName.'ToDbData($dbData);';

			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getType() === 'DocumentId')
			{
				$memberValue = ' = 0;';
			}

			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */
	private $' . $property->getName() . $memberValue . PHP_EOL;
		}

		$code .= '
	/**
	 * @api
	 */
	public function unsetProperties()
	{
		parent::unsetProperties();
		' . implode(PHP_EOL. '		', $unsetProperties) . '
	}' . PHP_EOL;

		if (count($fromDbData))
		{
			$code .= '
	/**
	 * @api
	 * @param array $dbData
	 */
	public function fromDbData(array $dbData)
	{
		parent::fromDbData($dbData);
		' . implode(PHP_EOL. '		', $fromDbData) . '
	}' . PHP_EOL;
		}

		if (count($toDbData))
		{
			$code .= '
	/**
	 * @return array
	 */
	protected function toDbData()
	{
		$dbData = parent::toDbData();
		' . implode(PHP_EOL. '		', $toDbData) . '
		return $dbData;
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
		$eType = $this->escapePHPValue($property->getType());

		if ($property->getType() === 'Date')
		{
			$code = '
	/**
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = '.$mn.' instanceof \DateTime ? '.$mn.'->format(\'Y-m-d\') : null;
		return $dbData;
	}';
		}
		elseif ($property->getType() === 'DateTime')
		{
			$code = '
	/**
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = '.$mn.' instanceof \DateTime ? '.$mn.'->format(\DateTime::ISO8601) : null;
		return $dbData;
	}';
		}
		else
		{
			$code = '
	/**
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = '.$mn.';
		return $dbData;
	}';
		}

		$code .= '

	/**
	 * @param array $dbData
	 */
	private function '.$name.'FromDbData($dbData)
	{
		'.$mn.' = $this->convertToInternalValue(isset($dbData['.$en.']) ? $dbData['.$en.'] : null, '.$eType.');
	}

	/**
	 * @param ' . $ct . '|null ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		' . $this->buildValConverter($property, $var) . ';
		if (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			$this->onPropertyUpdate();
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
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = (is_array('.$mn.') && count('.$mn.')) ? '.$mn.' : null;
		return $dbData;
	}

	/**
	 * @param array $dbData
	 */
	private function '.$name.'FromDbData($dbData)
	{
		'.$mn.' = (isset($dbData['.$en.']) && is_array($dbData['.$en.']) && count($dbData['.$en.'])) ? $dbData['.$en.'] : null;
	}

	/**
	 * @param '. $ct .'|null ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		'.$var.' = (is_array('.$var.') && count('.$var.')) ? '.$var.' : null;
		if (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			$this->onPropertyUpdate();
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
		$en = $this->escapePHPValue($name);
		$code = '
	/**
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = (' . $mn . '  instanceof ' . $ct . ' && !' . $mn . '->isEmpty()) ? '.$mn.'->toArray() : null;
		return $dbData;
	}

	/**
	 * @param array $dbData
	 */
	private function '.$name.'FromDbData($dbData)
	{
		'.$mn.' = new ' . $ct . '(isset($dbData['.$en.']) ? $dbData['.$en.'] : null);
	}

	/**
	 * @param string|array|' . $ct . '|null ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if (!(' . $mn . '  instanceof ' . $ct . '))
		{
			' . $mn . ' = new ' . $ct . '(null);
		}
		if (!(' . $var . '  instanceof ' . $ct . '))
		{
			' . $var . ' = new ' . $ct . '(' . $var . ');
		}
		if (' . $this->buildNotEqualsProperty($mn . '->toArray()', $var. '->toArray()', $property->getType()) . ')
		{
			$this->onPropertyUpdate();
			' . $mn . ' = ' . $var . ';
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if (!(' . $mn . '  instanceof ' . $ct . '))
		{
			' . $mn . ' = new ' . $ct . '(null);
		}
		return ' . $mn . ';
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
			case 'JSON' :
				return 'array';
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
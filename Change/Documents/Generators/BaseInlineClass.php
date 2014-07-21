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
 * @name \Change\Documents\Generators\BaseInlineClass
 * @api
 */
class BaseInlineClass
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
		$nsParts[] = $model->getShortBaseDocumentClassName() . '.php';
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
		$code = '<' . '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		$code .= '
/**
 * @name ' . $model->getBaseDocumentClassName() . '
 * @method ' . $model->getModelClassName() . ' getDocumentModel()'. PHP_EOL .
			($model->rootLocalized() ? ' * @method ' . $model->getDocumentLocalizedClassName() . ' getCurrentLocalization()'. PHP_EOL : '') .
			($model->rootLocalized() ? ' * @method ' . $model->getDocumentLocalizedClassName() . ' getRefLocalization()'. PHP_EOL : '') .
			' */' . PHP_EOL;


		$extend = $model->getParentDocumentClassName();

		$interfaces = array();
		$uses = array();

		// implements , 
		if ($model->getLocalized())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Localizable';
			$uses[] = '\Change\Documents\Traits\InlineLocalized';
		}
		if ($model->getActivable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Activable';
			$uses[] = '\Change\Documents\Traits\InlineActivation';
		}

		if (count($interfaces))
		{
			$extend .= ' implements ' . implode(', ', $interfaces);
		}

		$code .= 'abstract class ' . $model->getShortBaseDocumentClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{' . PHP_EOL;
		if (count($uses))
		{
			$code .= '	use ' . implode(', ', $uses) . ';'. PHP_EOL;
		}

		$properties = $this->getMemberProperties($model);

		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);

			foreach ($properties as $property)
			{
				if ($property->getLocalized())
				{
					continue;
				}

				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getStateless())
				{
					$code .= $this->getPropertyStatelessCode($model, $property);
				}
				elseif ($property->getType() === 'JSON')
				{
					$code .= $this->getPropertyJSONAccessors($model, $property);
				}
				elseif ($property->getType() === 'RichText')
				{
					$code .= $this->getPropertyRichTextAccessors($model, $property);
				}
				elseif ($property->getType() === 'DocumentArray')
				{
					$code .= $this->getPropertyDocumentArrayAccessors($model, $property);
				}
				elseif ($property->getType() === 'Document')
				{
					$code .= $this->getPropertyDocumentAccessors($model, $property);
				}
				else
				{
					$code .= $this->getPropertyAccessors($model, $property);
				}
			}
		}

		$code .= '}' . PHP_EOL;
		$this->compiler = null;
		return $code;
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
		$unsetProperties = [];
		$fromDbData = [];
		$toDbData = [];
		if ($model->getLocalized())
		{
			$unsetProperties[] = '$this->resetCurrentLocalized();';
			$fromDbData[] = '$this->localizedDbData(isset($dbData[\'_LCID\']) ? $dbData[\'_LCID\'] : null);';
			$toDbData[] = '$dbData[\'_LCID\'] = $this->localizedDbData();';
		}

		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$propertyName = $property->getName();
			if ($property->getStateless() || $property->getLocalized())
			{
				continue;
			}

			$memberValue = ' = null;';
			if ($property->getType() == 'Document' || $property->getType() == 'DocumentId')
			{
				$memberValue = ' = 0;';
			}
			$unsetProperties[] = '$this->' . $propertyName . $memberValue;
			$fromDbData[] = '$this->'.$propertyName.'FromDbData($dbData);';
			$toDbData[] = '$dbData = $this->'.$propertyName.'ToDbData($dbData);';
			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */	
	private $' . $propertyName . $memberValue . PHP_EOL;
		}

		if ($model->getLocalized())
		{
			$code .= '
	public function cleanUp()
	{
		parent::cleanUp();
		$this->cleanUpLocalized();
	}' . PHP_EOL;
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
			case 'Inline' :
			case 'InlineArray' :
				return $this->compiler->getModelByName($property->getInlineType())->getDocumentClassName();
			case 'JSON' :
				return 'array';
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
			case 'Document' :
				return 'integer';
			case 'DocumentArray' :
				return 'integer|\Change\Documents\DocumentArrayProperty';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			case 'RichText' :
				return '\Change\Documents\RichtextProperty';
			case 'Inline' :
				return $this->compiler->getModelByName($property->getInlineType())->getDocumentClassName();
			case 'InlineArray' :
				return '\Change\Documents\InlineArrayProperty';
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
		return $varName . ' = $this->convertToInternalValue(' . $varName . ', ' . $this->escapePHPValue($property->getType()) . ')';
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
			return '$this->compareFloat(' . $oldVarName . ', ' . $newVarName . ')';
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
			return '!$this->compareFloat(' . $oldVarName . ', ' . $newVarName . ')';
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
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return ' . $mn . ';
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		' . $this->buildValConverter($property, $var) . ';
		if (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			$this->onPropertyUpdate('.$en.');
			' . $mn . ' = ' . $var . ';
		}
		return $this;
	}' . PHP_EOL;

		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyStatelessCode($model, $property)
	{
		if (in_array($property->getName(), array('creationDate', 'modificationDate')))
		{
			return '';
		}
		$code = array();
		$name = $property->getName();
		$var = '$' . $name;
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

			$code[] = '
	/**
	 * @return ' . $ct . '
	 */
	abstract public function get' . $un . '();

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	abstract public function set' . $un . '(' . $var . ');';

		if ($property->getType() === 'JSON')
		{
			$code[] = '
	/**
	 * @return string|null
	 */
	public function get' . $un . 'String()
	{
		' . $var . ' = $this->get' . $un . '();
		return (' . $var . ' === null) ? null : \Zend\Json\Json::encode(' . $var . ');
	}';
		}
		elseif ($property->getType() === 'Document')
		{
			$code[] = '
	/**
	 * @return integer|null
	 */
	public function get' . $un . 'Id()
	{
		' . $var . ' = $this->get' . $un . '();
		return ' . $var . ' instanceof \Change\Documents\AbstractDocument ? ' . $var . '->getId() : null;
	}';
		}
		elseif ($property->getType() === 'DocumentArray')
		{
			$code[] = '
	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$result = array();
		' . $var . ' = $this->get' . $un . '();
		if (is_array(' . $var . '))
		{
			foreach (' . $var . ' as $o)
			{
				if ($o instanceof \Change\Documents\AbstractDocument) {$result[] = $o->getId();}
			}
		}
		return $result;
	}';
		}

		$code[] = $this->getPropertyExtraGetters($model, $property);
		return implode(PHP_EOL, $code);
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
		$un = ucfirst($name);
		if ($property->getType() === 'DocumentId')
		{
			if ($property->getDocumentType() === null)
			{
				$ct = '\Change\Documents\AbstractDocument';
			}
			else
			{
				$ct = $this->compiler->getModelByName($property->getDocumentType())->getDocumentClassName();
			}

			$code .= '
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'Instance()
	{
		return $this->getDocumentManager()->getDocumentInstance($this->get' . $un . '());
	}' . PHP_EOL;
		}
		elseif ($property->getType() === 'StorageUri')
		{
			$code .= '
	/**
	 * @return \Change\Storage\ItemInfo|null
	 */
	public function get' . $un . 'ItemInfo(\Change\Storage\StorageManager $storageManager)
	{
		$uri = $this->get' . $un . '();
		if ($uri)
		{
			return $storageManager->getItemInfo($uri);
		}
		return null;
	}' . PHP_EOL;
		}
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyJSONAccessors($model, $property)
	{
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
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
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		return ' . $mn . ';
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		'.$var.' = (is_array('.$var.') && count('.$var.')) ? '.$var.' : null;
		if (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			$this->onPropertyUpdate('.$en.');
			' . $mn . ' = ' . $var . ';
		}
		return $this;
	}' . PHP_EOL;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyRichTextAccessors($model, $property)
	{
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
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
			$this->onPropertyUpdate('.$en.');
			' . $mn . ' = ' . $var . ';
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '
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
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentAccessors($model, $property)
	{
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
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
		$dbData['.$en.'] = '.$mn.';
		return $dbData;
	}

	/**
	 * @param array $dbData
	 */
	private function '.$name.'FromDbData($dbData)
	{
		'.$mn.' = (isset($dbData['.$en.'])) ? intval($dbData['.$en.']): 0;
	}

	/**
	 * @param ' . $ct . '|null ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ' = null)
	{
		if (' . $var . ' instanceof ' . $ct . ')
		{
			if (' . $var . '->getId() <= 0)
			{
				throw new \InvalidArgumentException(\'Argument 1 must be a saved document\', 52005);
			}
		}
		elseif (' . $var . ' !== null)
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an ' . $ct . '\', 52005);
		}
		$newId = (' . $var . ' !== null) ? ' . $var . '->getId() : 0;
		if (' . $mn . ' !== $newId)
		{
			$this->onPropertyUpdate();
			' . $mn . ' = $newId;
		}
		return $this;
	}

	/**
	 * @return integer
	 */
	public function get' . $un . 'Id()
	{
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return (' . $mn . ') ? $this->getDocumentManager()->getDocumentInstance(' . $mn . ') : null;
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
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$modelName = $this->escapePHPValue($property->getDocumentType(), false);
		$un = ucfirst($name);
		$code = '
	private function check'.$un.'Initialized()
	{
		if ('.$mn.' === null)
		{
			' . $mn . ' = new \Change\Documents\DocumentArrayProperty($this->getDocumentManager(), '.$modelName.');
		}
	}

	/**
	 * @param array $dbData
	 * @return array
	 */
	private function '.$name.'ToDbData($dbData)
	{
		$dbData['.$en.'] = ('.$mn.' instanceof \Change\Documents\DocumentArrayProperty) ? '.$mn.'->getIds() : null;
		return $dbData;
	}

	/**
	 * @param array $dbData
	 */
	private function '.$name.'FromDbData($dbData)
	{
		'.$mn.' = null;
		$this->check'.$un.'Initialized();
		if (isset($dbData['.$en.']) && is_array($dbData['.$en.']))
		{
			'.$mn.'->setDefaultIds($dbData['.$en.']);
		}
	}

	/**
	 * @param ' . $ct . '[] ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->onPropertyUpdate();
		$this->check'.$un.'Initialized();
		' . $mn . '->fromArray(' . $var . ');
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentArrayProperty|' . $ct . '[]
	 */
	public function get' . $un . '()
	{
		$this->check'.$un.'Initialized();
		return ' . $mn . ';
	}

	/**
	 * @return integer
	 */
	public function get' . $un . 'Count()
	{
		return (' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty) ? ' . $mn . '->count() : 0;
	}

	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$this->check'.$un.'Initialized();
		return ' . $mn . '->getIds();
	}' . PHP_EOL;

		return $code;
	}
}
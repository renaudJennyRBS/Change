<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\BaseDocumentClass
 * @api
 */
class BaseDocumentClass
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
			($model->checkLocalized() ? ' * @method ' . $model->getDocumentLocalizedClassName() . ' getCurrentLocalization()'. PHP_EOL : '') .
			' */' . PHP_EOL;

		$parentModel = $model->getParent();
		$extend = $parentModel ? $parentModel->getDocumentClassName() : '\Change\Documents\AbstractDocument';

		$interfaces = array();
		$uses = array();

		if ($model->getExtends() === null)
		{
			if ($model->getStateless())
			{
				$uses[] = '\Change\Documents\Traits\Stateless';
			}
			else
			{
				$uses[] = '\Change\Documents\Traits\DbStorage';
				if ($model->implementCorrection())
				{
					$interfaces[] = '\Change\Documents\Interfaces\Correction';
					$uses[] = '\Change\Documents\Traits\Correction';
				}
			}
		}

		// implements , 
		if ($model->getLocalized())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Localizable';
			$uses[] = '\Change\Documents\Traits\Localized';
		}
		if ($model->getEditable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Editable';
		}
		if ($model->getPublishable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Publishable';
			$uses[] = '\Change\Documents\Traits\Publication';
		}
		if ($model->getActivable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Activable';
		}
		if ($model->getUseVersion())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Versionable';
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
			if (!$model->checkStateless())
			{
				$code .= $this->getMembers($model, $properties);
			}

			foreach ($properties as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getStateless() || $model->checkStateless())
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
				elseif ($property->getType() === 'Object')
				{
					$code .= $this->getPropertyObjectAccessors($model, $property);
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

		if ($model->getEditable())
		{
			$code .= $this->getEditableInterface($model);
		}

		if ($model->getActivable())
		{
			$code .= $this->getActivableInterface($model);
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
	 * @return string
	 */
	protected function getActivableInterface($model)
	{
		$code = '
	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function activated(\DateTime $at = null)
	{
		if ($this->getActive())
		{
			$st = $this->getStartActivation();
			$ep = $this->getEndActivation();
			$test = ($at === null) ? new \DateTime() : $at;
			return (null === $st || $st <= $test) && (null === $ep || $test < $ep);
		}
		return false;
	}' . PHP_EOL;

		return $code;
	}
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getEditableInterface($model)
	{
		$code = '
	/**
	 * @return integer
	 */
	public function nextDocumentVersion()
	{
		$next = max(0, $this->getDocumentVersion()) + 1;
		$this->setDocumentVersion($next);
		return $next;
	}' . PHP_EOL;

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
		$modifiedProperties = array();
		$removeOldPropertiesValue = array();
		if ($model->getLocalized())
		{
			$resetProperties[] = '$this->resetCurrentLocalized();';
			$modifiedProperties[] = '$names = array_merge($names, $this->getCurrentLocalization()->getModifiedPropertyNames());';
		}
		if ($model->implementCorrection())
		{
			$resetProperties[] = '$this->corrections = null;';
		}

		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$propertyName = $property->getName();
			if ($property->getStateless())
			{
				continue;
			}
			elseif ($property->getLocalized())
			{
				$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': $this->getCurrentLocalization()->removeOldPropertyValue($propertyName); return;';
				continue;
			}

			if ($property->getType() === 'DocumentArray')
			{
				$memberValue =  ' = 0;';
				$modifiedProperties[] = 'if ($this->'.$propertyName.' instanceof \Change\Documents\DocumentArrayProperty && $this->'.$propertyName.'->isModified()) {$names[] = \''.$propertyName.'\';}';
				$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': if ($this->'.$propertyName.' instanceof \Change\Documents\DocumentArrayProperty) {$this->'.$propertyName.'->setAsDefault();} return;';
			}
			elseif ($property->getType() === 'RichText')
			{
				$memberValue =  ' = null;';
				$modifiedProperties[] = 'if ($this->'.$propertyName.' !== null && $this->'.$propertyName.'->isModified()) {$names[] = \''.$propertyName.'\';}';
				$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': if ($this->'.$propertyName.' !== null) {$this->'.$propertyName.'->setAsDefault();} return;';
			}
			elseif ($property->getType() === 'Document' || $property->getType() === 'DocumentId')
			{
				$memberValue = ' = 0;';
				$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': unset($this->modifiedProperties[\''.$propertyName.'\']); return;';
			}
			else
			{
				$memberValue = ' = null;';
				$removeOldPropertiesValue[] = 'case \''.$propertyName.'\': unset($this->modifiedProperties[\''.$propertyName.'\']); return;';
			}

			$resetProperties[] = '$this->' . $propertyName . $memberValue;
			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */	
	private $' . $propertyName . $memberValue . PHP_EOL;
		}

		$code .= '
	/**
	 * @api
	 */
	public function unsetProperties()
	{
		parent::unsetProperties();
		' . implode(PHP_EOL. '		', $resetProperties) . '
	}' . PHP_EOL;

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
		' . implode(PHP_EOL. '		', $modifiedProperties) . '
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
			case 'JSON' :
				return 'array';
			case 'RichText' :
				return '\Change\Documents\RichtextProperty';
			case 'Object' :
				return 'mixed';
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
		return
			$varName . ' = $this->convertToInternalValue(' . $varName . ', ' . $this->escapePHPValue($property->getType()) . ')';
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
		$code = '';
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		if (!$property->getLocalized())
		{
			$code .= '
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}

	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		$this->load();
		return ' . $mn . ';
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		' . $this->buildValConverter($property, $var) . ';
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
			return $this;
		}
		$this->load();
		if (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
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
	}' . PHP_EOL;
		}
		else
		{
			$code .= '
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getCurrentLocalization()->get' . $un . 'OldValue();
	}

	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		return $this->getCurrentLocalization()->get' . $un . '();
	}' . PHP_EOL;

			if ($name === 'LCID')
			{
				$code .= '
	/**
	 * Has no effect.
	 * @see \Change\Documents\DocumentManager::pushLCID()
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		return $this;
	}' . PHP_EOL;
			}
			else
			{
				$code .= '
	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		' . $this->buildValConverter($property, $var) . ';
		$this->getCurrentLocalization()->set' . $un . '(' . $var . ');
		return $this;
	}' . PHP_EOL;
			}
		}

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

		if ($name === 'publicationSections')
		{
			$code[] = '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		return array();
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		return $this;
	}';
		}
		else
		{
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
		}

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
		if ($property->getType() === 'Object')
		{
			$code[] = '
	/**
	 * @return string|null
	 */
	public function get' . $un . 'String()
	{
		' . $var . ' = $this->get' . $un . '();
		return (' . $var . ' === null) ? null : serialize(' . $var . ');
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
		$var = '$' . $name;
		$un = ucfirst($name);
		$ct = $this->getCommentaryType($property);

		if ($property->getType() === 'XML')
		{
			$code .= '
	/**
	 * @return \DOMDocument
	 */
	public function get' . $un . 'DOMDocument()
	{
		$document = new \DOMDocument("1.0", "UTF-8");
		if ($this->get' . $un . '() !== null) {$document->loadXML($this->get' . $un . '());}
		return $document;
	}
		
	/**
	 * @param \DOMDocument $document
	 * @return $this
	 */
	public function set' . $un . 'DOMDocument($document)
	{
		$this->set' . $un . '($document && $document->documentElement ? $document->saveXML() : null);
		return $this;
	}' . PHP_EOL;
		}
		elseif ($property->getType() === 'DocumentId')
		{
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
	public function get' . $un . 'ItemInfo()
	{
		$uri = $this->get' . $un . '();
		if ($uri)
		{
			return $this->getApplicationServices()->getStorageManager()->getItemInfo($uri);
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
		$code = '';
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		if (!$property->getLocalized())
		{
			$code .= '
	/**
	 * @return string|null
	 */
	public function get' . $un . 'OldStringValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->get' . $un . 'OldStringValue();
		return ' . $var . ' === null ? ' . $var . ' : \Zend\Json\Json::decode(' . $var . ', \Zend\Json\Json::TYPE_ARRAY);
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ' === null ? null : ' . $var . ';
			return $this;
		}
		if (' . $var . ' !== null && !is_array(' . $var . '))
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an ' . $ct . ' or null\', 52005);
		}
		$this->load();
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
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		$this->load();
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		$this->load();
		return ' . $mn . ' === null ? null : \Zend\Json\Json::decode(' . $mn . ', \Zend\Json\Json::TYPE_ARRAY);
	}' . PHP_EOL;
		}
		else
		{
			$code .= '
	/**
	 * @return string|null
	 */
	public function get' . $un . 'OldStringValue()
	{
		return $this->getCurrentLocalization()->get' . $un . 'OldStringValue();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->get' . $un . 'OldStringValue();
		return ' . $var . ' === null ? ' . $var . ' : \Zend\Json\Json::decode(' . $var . ', \Zend\Json\Json::TYPE_ARRAY);
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		$this->getCurrentLocalization()->set' . $un . '(' . $var . ');
		return $this;
	}

	/**
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		return $this->getCurrentLocalization()->get' . $un . 'String();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return $this->getCurrentLocalization()->get' . $un . '();
	}' . PHP_EOL;
		}
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyRichTextAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		if (!$property->getLocalized())
		{
			$code .= '
	protected function checkLoaded' . $un . '()
	{
		$this->load();
		if (' . $mn . ' === null) {' . $mn . ' = new ' . $ct . '();}
	}

	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . 'OldValue()
	{
		return new ' . $ct . '((' . $mn . ' !== null) ? ' . $mn . '->getDefaultJSONString() : null);
	}

	/**
	 * @param string|array|' . $ct . '|null ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = new ' . $ct . '(' . $var . ');
			return $this;
		}
		$this->checkLoaded' . $un . '();

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
		else
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an array, string, ' . $ct . ' or null\', 52005);
		}
		return $this;
	}

	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return (' . $mn . ' !== null) ? ' . $mn . '->toJSONString() : null;
		}
		$this->checkLoaded' . $un . '();
		return ' . $mn . ';
	}' . PHP_EOL;
		}
		else
		{
			$code .= '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getCurrentLocalization()->get' . $un . 'OldValue();
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		$this->getCurrentLocalization()->set' . $un . '(' . $var . ');
		return $this;
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return $this->getCurrentLocalization()->get' . $un . '();
	}' . PHP_EOL;
		}
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyObjectAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		if (!$property->getLocalized())
		{
			$code .= '
	/**
	 * @return string|null
	 */
	public function get' . $un . 'OldStringValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->get' . $un . 'OldStringValue();
		return ' . $var . ' === null ? ' . $var . ' : unserialize(' . $var . ');
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ' === null ? null : ' . $var . ';
			return $this;
		}
		$this->load();
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
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		$this->load();
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		$this->load();
		return ' . $mn . ' === null ? null : unserialize(' . $mn . ');
	}' . PHP_EOL;
		}
		else
		{
			$code .= '

	/**
	 * @return string|null
	 */
	public function get' . $un . 'OldStringValue()
	{
		return $this->getCurrentLocalization()->get' . $un . 'OldStringValue();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		' . $var . ' = $this->get' . $un . 'OldStringValue();
		return ' . $var . ' === null ? ' . $var . ' : unserialize(' . $var . ');
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		$this->getCurrentLocalization()->set' . $un . '(' . $var . ');
		return $this;
	}

	/**
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		return $this->getCurrentLocalization()->get' . $un . 'String();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		return $this->getCurrentLocalization()->get' . $un . '();
	}' . PHP_EOL;
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
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		$code .= '
	/**
	 * @return integer|null
	 */
	public function get' . $un . 'OldValueId()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		$oldId = $this->get' . $un . 'OldValueId();
		return ($oldId !== null) ? $this->getDocumentManager()->getDocumentInstance($oldId) : null;
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ' = null)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = max(0, intval(' . $var . '));
			return $this;
		}
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
		$this->load();
		$newId = (' . $var . ' !== null) ? ' . $var . '->getId() : 0;
		if (' . $mn . ' !== $newId)
		{
			if (array_key_exists(' . $en . ', $this->modifiedProperties))
			{
				if ($this->modifiedProperties[' . $en . '] === $newId)
				{
					unset($this->modifiedProperties[' . $en . ']);
				}
			}
			else
			{
				$this->modifiedProperties[' . $en . '] = ' . $mn . ';
			}
			' . $mn . ' = $newId;
		}
		return $this;
	}

	/**
	 * @return integer
	 */
	public function get' . $un . 'Id()
	{
		$this->load();
		return ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		$this->load();
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
		$code = '';
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$modelName = $this->escapePHPValue($property->getDocumentType(), false);
		$un = ucfirst($name);
		$code .= '
	protected function checkLoaded' . $un . '()
	{
		$this->load();
		if (!(' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty))
		{
			' . $mn . ' = new \Change\Documents\DocumentArrayProperty($this->getDocumentManager(), '.$modelName.');
			$ids = $this->getDocumentManager()->getPropertyDocumentIds($this, ' . $en . ');
			' . $mn . '->setDefaultIds($ids);
		}
	}

	/**
	 * @param ' . $ct . '[] ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = intval(' . $var . ');
			return $this;
		}
		$this->checkLoaded' . $un . '();
		' . $mn . '->fromArray(' . $var . ');
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentArrayProperty|' . $ct . '[]
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return (' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty) ? ' . $mn . '->count() : ' . $mn . ';
		}
		$this->checkLoaded' . $un . '();
		return ' . $mn . ';
	}

	/**
	 * @return integer
	 */
	public function get' . $un . 'Count()
	{
		$this->load();
		return (' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty) ? ' . $mn . '->count() : ' . $mn . ';
	}

	/**
	 * @return ' . $ct . '[]
	 */
	public function get' . $un . 'OldValue()
	{
		if (' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty && ' . $mn . '->isModified())
		{
			return ' . $mn . '->getDefaultDocuments();
		}
		return array();
	}

	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$this->checkLoaded' . $un . '();
		return ' . $mn . '->getIds();
	}

	/**
	 * @return integer[]
	 */
	public function get' . $un . 'OldValueIds()
	{
		if (' . $mn . ' instanceof \Change\Documents\DocumentArrayProperty && ' . $mn . '->isModified())
		{
			return ' . $mn . '->getDefaultIds();
		}
		return array();
	}' . PHP_EOL;

		return $code;
	}
}
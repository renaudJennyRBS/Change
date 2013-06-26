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
		if ($model->getLocalized())
		{
			$resetProperties[] = '		$this->resetCurrentLocalized();';
		}
		if ($model->implementCorrection())
		{
			$resetProperties[] = '		$this->corrections = null;';
		}

		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized() || $property->getStateless())
			{
				continue;
			}
			$resetProperties[] = '		$this->' . $property->getName() . ' = null;';
			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */	
	private $' . $property->getName() . ';' . PHP_EOL;
		}

		$code .= '		
	/**
	 * @api
	 */
	public function unsetProperties()
	{
		parent::unsetProperties();' . PHP_EOL . implode(PHP_EOL, $resetProperties) . '
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
		$code .= '
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}' . PHP_EOL;

		if (!$property->getLocalized())
		{
			$code .= '
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
			$this->propertyChanged(' . $en . ');
		}
		return $this;
	}' . PHP_EOL;
		}
		else
		{
			$code .= '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		$localizedPart = $this->getCurrentLocalization();
		return $localizedPart->get' . $un . '();
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
		$localizedPart = $this->getCurrentLocalization();
		if ($localizedPart->set' . $un . '(' . $var . '))
		{
			$this->removeOldPropertyValue(' . $en . ');
			if ($localizedPart->isPropertyModified(' . $en . '))
			{
				$this->setOldPropertyValue(' . $en . ', $localizedPart->get' . $un . 'OldValue());
			}
			$this->propertyChanged(' . $en . ');
		}
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
	}' . PHP_EOL;

		if (!$property->getLocalized())
		{
			$code .= '
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
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal !== $newString)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newString;
			$this->propertyChanged(' . $en . ');
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
	 * @param ' . $ct . ' ' . $var . '
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		$localizedPart = $this->getCurrentLocalization();
		if ($localizedPart->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			$localizedPart->set' . $un . 'String(' . $var . ');
			return $this;
		}
		if (' . $var . ' !== null && !is_array(' . $var . '))
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an ' . $ct . ' or null\', 52005);
		}
		$newString = (' . $var . ' !== null) ? \Zend\Json\Json::encode(' . $var . ') : null;
		if ($localizedPart->set' . $un . 'String($newString))
		{
			$this->removeOldPropertyValue(' . $en . ');
			if ($localizedPart->isPropertyModified(' . $en . '))
			{
				$this->setOldPropertyValue(' . $en . ', $localizedPart->get' . $un . 'OldStringValue());
			}
			$this->propertyChanged(' . $en . ');
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		$localizedPart = $this->getCurrentLocalization();
		return $localizedPart->get' . $un . 'String();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		$localizedPart = $this->getCurrentLocalization();
		' . $var . ' = $localizedPart->get' . $un . 'String();
		if ($localizedPart->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $var . ';
		}
		return ' . $var . ' === null ? null : \Zend\Json\Json::decode(' . $var . ', \Zend\Json\Json::TYPE_ARRAY);
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
	}' . PHP_EOL;

		if (!$property->getLocalized())
		{
			$code .= '
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
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal !== $newString)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newString;
			$this->propertyChanged(' . $en . ');
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
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function set' . $un . '(' . $var . ')
	{
		$this->load();
		$localizedPart = $this->getCurrentLocalization();
		if ($localizedPart->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			$localizedPart->set' . $un . 'String(' . $var . ');
			return $this;
		}
		$newString = (' . $var . ' !== null) ? serialize(' . $var . ') : null;
		if ($localizedPart->set' . $un . 'String($newString))
		{
			$this->removeOldPropertyValue(' . $en . ');
			if ($localizedPart->isPropertyModified(' . $en . '))
			{
				$this->setOldPropertyValue(' . $en . ', $localizedPart->get' . $un . 'OldStringValue());
			}
			$this->propertyChanged(' . $en . ');
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function get' . $un . 'String()
	{
		$localizedPart = $this->getCurrentLocalization();
		return $localizedPart->get' . $un . 'String();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		$localizedPart = $this->getCurrentLocalization();
		' . $var . ' = $localizedPart->get' . $un . 'String();
		if ($localizedPart->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $var . ';
		}
		return ' . $var . ' === null ? null : unserialize(' . $var . ');
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
			' . $mn . ' = ' . $var . ' === null ? null : intval(' . $var . ');
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
		$newId = (' . $var . ' !== null) ? ' . $var . '->getId() : null;
		if (' . $mn . ' !== $newId)
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal !== $newId)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newId;
			$this->propertyChanged(' . $en . ');
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
		$un = ucfirst($name);
		$code .= '
	protected function checkLoaded' . $un . '()
	{
		$this->load();
		if (!is_array(' . $mn . '))
		{
			if (' . $mn . ')
			{
				' . $mn . ' = $this->getDocumentManager()->getPropertyDocumentIds($this, ' . $en . ');
			}
			else
			{
				' . $mn . ' = array();
			}
		}
	}

	/**
	 * @return integer[]
	 */
	public function get' . $un . 'OldValueIds()
	{
		$result = $this->getOldPropertyValue(' . $en . ');
		return (is_array($result)) ? $result : array();
	}

	/**
	 * @return ' . $ct . '[]
	 */
	public function get' . $un . 'OldValue()
	{
		$dm = $this->getDocumentManager();
		return array_map(function ($documentId) use ($dm) {
			return $dm->getDocumentInstance($documentId);
			}, $this->get' . $un . 'OldValueIds());
	}

	/**
	 * @return ' . $ct . '[]
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return is_array(' . $mn . ') ? count(' . $mn . ') : ' . $mn . ';
		}
		$this->checkLoaded' . $un . '();
		$dm = $this->getDocumentManager();
		$documents = array();
		foreach(' . $mn . ' as $documentId)
		{
			$document = $dm->getDocumentInstance($documentId);
			if ($document instanceof ' . $ct . ')
			{
				$documents[] = $document;
			}
		}
		return $documents;
	}

	/**
	 * @param ' . $ct . '[] $newValueArray
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . '($newValueArray)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = intval($newValueArray);
			return $this;
		}
		if (!is_array($newValueArray))
		{
			throw new \InvalidArgumentException(\'Argument 1 must be an array\', 52005);
		}
		$this->checkLoaded' . $un . '();

		$newValueIds = array_map(function($newValue) {
			if ($newValue instanceof ' . $ct . ')
			{
				if ($newValue->getId() <= 0)
				{
					throw new \InvalidArgumentException(\'Argument 1 must be a saved document\', 52005);
				}
				return $newValue->getId();
			}
			else
			{
				throw new \InvalidArgumentException(\'Argument 1 must be a ' . $ct . '[]\', 52005);
			}
		}, $newValueArray);
		$this->setInternal' . $un . 'Ids($newValueIds);
		return $this;
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return $this
	 */
	public function add' . $un . '(' . $ct . ' ' . $var . ')
	{
		$this->set' . $un . 'AtIndex(' . $var . ', -1);
		return $this;
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @param integer $index
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function set' . $un . 'AtIndex(' . $ct . ' ' . $var . ', $index = 0)
	{
		$this->checkLoaded' . $un . '();
		$newId = ' . $var . '->getId();
		if ($newId <= 0)
		{
			throw new \InvalidArgumentException(\'Argument 1 must be a saved document\', 52005);
		}
		if (!in_array($newId, ' . $mn . '))
		{
			$newValueIds = ' . $mn . ';
			$index = intval($index);
			if ($index < 0 || $index > count($newValueIds))
			{
				$index = count($newValueIds);
			}
			$newValueIds[$index] = $newId;
			$this->setInternal' . $un . 'Ids($newValueIds);
		}
		return $this;
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return boolean
	 */
	public function remove' . $un . '(' . $ct . ' ' . $var . ')
	{
		$index = $this->getIndexof' . $un . '(' . $var . ');
		if ($index !== -1)
		{
			return $this->remove' . $un . 'ByIndex($index);
		}
		return false;
	}

	/**
	 * @param integer $index
	 * @return boolean
	 */
	public function remove' . $un . 'ByIndex($index)
	{
		$this->checkLoaded' . $un . '();
		if (isset(' . $mn . '[$index]))
		{
			$newValueIds = ' . $mn . ';
			unset($newValueIds[$index]);
			$this->setInternal' . $un . 'Ids($newValueIds);
			return true;
		}
		return false;
	}

	/**
	 * @return $this
	 */
	public function removeAll' . $un . '()
	{
		$this->checkLoaded' . $un . '();
		$this->setInternal' . $un . 'Ids(array());
		return $this;
	}

	/**
	 * @param integer[] $newValueIds
	 */
	protected function setInternal' . $un . 'Ids(array $newValueIds)
	{
		if (' . $mn . ' != $newValueIds)
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal == $newValueIds)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newValueIds;
			$this->propertyChanged(' . $en . ');
		}
	}

	/**
	 * @param integer $index
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'ByIndex($index)
	{
		$this->checkLoaded' . $un . '();
		return isset(' . $mn . '[$index]) ?  $this->getDocumentManager()->getDocumentInstance(' . $mn . '[$index]) : null;
	}

	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$this->checkLoaded' . $un . '();
		return ' . $mn . ';
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return integer
	 */
	public function getIndexof' . $un . '(' . $ct . ' ' . $var . ')
	{
		$this->checkLoaded' . $un . '();
		$valueId = ' . $var . '->getId();
		$index = array_search($valueId, ' . $mn . ');
		return $index !== false ? $index : -1;
	}' . PHP_EOL;
		return $code;
	}
}
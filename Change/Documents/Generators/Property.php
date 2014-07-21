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
 * @name \Change\Documents\Generators\Property
 */
class Property
{
	protected static $TYPES = ['String', 'Boolean', 'Integer', 'Float', 'Decimal',
		'Date', 'DateTime', 'LongString', 'StorageUri', 'Lob', 'RichText', 'JSON',
		'DocumentId', 'Document', 'DocumentArray', 'Inline', 'InlineArray'];

	protected static $RESERVED_PROPERTY_NAMES = ['id', 'model', 'treename', 'meta', 'metas', 'reflcid', 'lcid',
		'creationdate', 'modificationdate', 'authorname', 'authorid', 'documentversion',
		'publicationstatus', 'startpublication', 'endpublication', 'versionofid',
		'active', 'startactivation', 'endactivation'];

	protected static $COMMON_PROPERTY_NAMES = ['label', 'title', 'publicationsections', 'code'];

	/**
	 * @var \Change\Documents\Generators\Property
	 */
	protected $parent;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var boolean
	 */
	protected $stateless;

	/**
	 * @var string
	 */
	protected $documentType;

	/**
	 * @var string
	 */
	protected $inlineType;

	/**
	 * @var Model
	 */
	protected $inlineModel;

	/**
	 * @var boolean
	 */
	protected $internal;

	/**
	 * @var string
	 */
	protected $defaultValue;

	/**
	 * @var boolean
	 */
	protected $required;

	/**
	 * @var integer
	 */
	protected $minOccurs;

	/**
	 * @var integer
	 */
	protected $maxOccurs;

	/**
	 * @var boolean
	 */
	protected $localized;

	/**
	 * @var boolean
	 */
	protected $hasCorrection;

	/**
	 * @var array
	 */
	protected $constraintArray = [];

	/**
	 * @var array
	 */
	protected $dbOptions;

	/**
	 * @var string
	 */
	protected $labelKey;

	/**
	 * @return string[]
	 */
	public static function getReservedPropertyNames()
	{
		return static::$RESERVED_PROPERTY_NAMES;
	}

	/**
	 * @return string[]
	 */
	public static function getValidPropertyTypes()
	{
		return static::$TYPES;
	}

	/**
	 * @param Model $model
	 * @param string $name
	 * @param string $type
	 */
	public function __construct(Model $model, $name = null, $type = null)
	{
		$this->model = $model;
		$this->name = $name;
		$this->type = $type;
	}

	protected function checkReservedPropertyName($name)
	{
		if (in_array(strtolower($name), self::$RESERVED_PROPERTY_NAMES))
		{
			throw new \RuntimeException('Invalid name attribute value: ' . $name, 54022);
		}
	}

	/**
	 * @return Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param \Change\Documents\Generators\Property $parent
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return \Change\Documents\Generators\Property[]
	 */
	public function getAncestors()
	{
		if ($this->parent)
		{
			$ancestors = $this->parent->getAncestors();
			$ancestors[] = $this->parent;
			return $ancestors;
		}
		return array();
	}

	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public function getRoot()
	{
		return ($this->parent) ? $this->parent->getRoot() : $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return boolean
	 */
	public function getStateless()
	{
		return $this->stateless;
	}

	/**
	 * @return boolean
	 */
	public function getInternal()
	{
		return $this->internal;
	}

	/**
	 * @return string
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}

	/**
	 * @return string
	 */
	public function getInlineType()
	{
		return $this->inlineType;
	}

	/**
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function getRequired()
	{
		return $this->required;
	}

	/**
	 * @return integer
	 */
	public function getMinOccurs()
	{
		return $this->minOccurs;
	}

	/**
	 * @return integer
	 */
	public function getMaxOccurs()
	{
		return $this->maxOccurs;
	}

	/**
	 * @return boolean
	 */
	public function getLocalized()
	{
		return $this->localized;
	}

	/**
	 * @return boolean
	 */
	public function getHasCorrection()
	{
		return $this->hasCorrection;
	}

	/**
	 * @return array
	 */
	public function getConstraintArray()
	{
		return $this->constraintArray;
	}

	/**
	 * @return array|null
	 */
	public function getDbOptions()
	{
		return $this->dbOptions;
	}

	/**
	 * @param array|null $dbOptions
	 */
	public function setDbOptions($dbOptions)
	{
		$this->dbOptions = $dbOptions;
	}

	/**
	 * @return string
	 */
	public function getComputedType()
	{
		return $this->getRoot()->getType();
	}

	/**
	 * @return integer
	 */
	public function getComputedMinOccurs()
	{
		$p = $this;
		while ($p)
		{
			if ($p->getMinOccurs() !== null)
			{
				return $p->getMinOccurs();
			}
			$p = $p->getParent();
		}
		return 0;
	}

	/**
	 * @return integer
	 */
	public function getComputedMaxOccurs()
	{
		$p = $this;
		while ($p)
		{
			if ($p->getMaxOccurs() !== null)
			{
				return $p->getMaxOccurs();
			}
			$p = $p->getParent();
		}
		return 100;
	}

	/**
	 * @param boolean|null $localized
	 */
	public function makeLocalized($localized)
	{
		if ($this->stateless)
		{
			$localized = null;
		}
		$this->localized = $localized;
	}

	/**
	 * @param string $string
	 */
	public function setDefaultValue($string)
	{
		$this->defaultValue = $string;
	}

	/**
	 * @throws \Exception
	 */
	public function validateInheritance()
	{
		$model = $this->model;
		$parentProp = $this->initializeParent();

		if ($this->stateless && ($model->rootStateless() || $parentProp))
		{
			throw new \RuntimeException('Invalid stateless attribute on ' . $this, 54028);
		}

		if ($parentProp === null)
		{
			if ($this->type === null)
			{
				$this->type = 'String';
				$this->applyDefaultProperties();
			}

			$lcName = strtolower($this->name);
			if ($this->labelKey === null && in_array($lcName, array_merge(static::$RESERVED_PROPERTY_NAMES, static::$COMMON_PROPERTY_NAMES)))
			{
				$this->setLabelKey('c.documents.' . $lcName);
			}

			if ($this->inlineModel)
			{
				$this->inlineModel->validateInheritance();
			}
		}
		else
		{
			if ($this->type || $this->localized || $this->hasCorrection)
			{
				throw new \RuntimeException('Invalid type redefinition attribute on ' . $this, 54027);
			}

			if ($this->inlineModel)
			{
				$parentInlineModel = null;
				$pp = $parentProp;
				while ($pp)
				{
					$parentInlineModel = $pp->inlineModel;
					if ($parentInlineModel)
					{
						$this->inlineModel->setParent($parentInlineModel);
						$this->inlineModel->validateInheritance();
						break;
					}
					$pp = $pp->getParent();
				}

				if ($parentInlineModel === null)
				{
					throw new \RuntimeException('Invalid inline parent model: ' . $this, 999999);
				}
			}
		}

		if ($this->hasCorrection)
		{
			if ($this->name === 'label')
			{
				throw new \RuntimeException('Invalid has-correction attribute on ' . $this->model . '::' . $this, 54028);
			}
			$model->getRoot()->implementCorrection(true);
		}

		if ($model->rootLocalized())
		{
			switch ($this->name)
			{
				case 'refLCID':
				case 'versionOfId':
				case 'treeName':
				case 'label':
					$this->makeLocalized(null);
					break;

				case 'creationDate':
				case 'modificationDate':

				case 'LCID':
				case 'title':
				case 'authorName':
				case 'authorId':
				case 'documentVersion':

				case 'publicationStatus':
				case 'startPublication':
				case 'endPublication':

				case 'active':
				case 'startActivation':
				case 'endActivation':
					$this->makeLocalized(true);
			}
		}
		elseif ($this->localized)
		{
			throw new \RuntimeException('Invalid localized attribute on ' . $this, 54028);
		}

		$type = $this->getComputedType();
		if ($type === 'DocumentArray')
		{
			$mi = $this->getComputedMinOccurs();
			$ma = $this->getComputedMaxOccurs();
			if ($ma < $mi)
			{
				throw new \RuntimeException('Invalid min-occurs max-occurs attribute value on ' . $this, 54028);
			}

		}
		else
		{
			if ($this->minOccurs !== null)
			{
				throw new \RuntimeException('Invalid min-occurs attribute on ' . $this, 54028);
			}
			if ($this->maxOccurs !== null)
			{
				throw new \RuntimeException('Invalid max-occurs attribute on ' . $this, 54028);
			}

			if ($model->getRoot()->getInline())
			{
				if ($type === 'Inline' || $type === 'InlineArray')
				{
					throw new \RuntimeException('Invalid Inline or InlineArray type attribute on ' . $this, 54028);
				}
			}
		}
	}


	/**
	 * @return boolean
	 */
	public function hasRelation()
	{
		$type = $this->getComputedType();
		return ($type === 'Document' || $type === 'DocumentArray' || $type === 'DocumentId');
	}

	/**
	 * @return boolean|number|string
	 */
	public function getDefaultPhpValue()
	{
		$val = $this->getDefaultValue();
		if ($val !== null)
		{
			$type = $this->getComputedType();
			if ($type === 'Boolean')
			{
				return $val === 'true';
			}
			if ($type === 'Integer' || $type === 'DocumentId')
			{
				return intval($val);
			}
			if ($type === 'Float' || $type === 'Decimal')
			{
				return floatval($val);
			}
		}
		return $val;
	}

	/**
	 * @param boolean $stateless
	 */
	public function setStateless($stateless)
	{
		$this->stateless = ($stateless === true) ? true : null;
	}

	/**
	 * @param string $labelKey
	 */
	public function setLabelKey($labelKey)
	{
		$this->labelKey = $labelKey;
	}

	/**
	 * @return string
	 */
	public function getLabelKey()
	{
		if ($this->labelKey)
		{
			return $this->labelKey;
		}
		return \Change\Stdlib\String::toLower(implode('.', ['m', $this->getModel()->getVendor(), $this->getModel()->getShortModuleName(), 'documents', $this->getModel()->getShortName() . '_' . $this->getName()]));
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->model . '::' . $this->getName();
	}

	/**
	 * @param \DOMElement $xmlElement
	 * @param Compiler $compiler
	 * @throws \RuntimeException
	 */
	public function initialize($xmlElement, Compiler $compiler)
	{
		$this->importXmlAttributes($xmlElement);

		$this->importXmlChildNodes($xmlElement, $compiler);

		$this->applyDefaultProperties();
	}

	/**
	 * @param $xmlElement
	 * @throws \RuntimeException
	 */
	public function importXmlAttributes($xmlElement)
	{
		foreach ($xmlElement->attributes as $attribute)
		{
			/* @var $attribute \DOMNode */
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			$tv = trim($value);
			if ($tv == '' || $tv != $value)
			{
				throw new \RuntimeException('Invalid empty or spaced attribute value for ' . $name, 54021);
			}

			switch ($name)
			{
				case "name":
					$this->checkReservedPropertyName($value);
					$this->name = $value;
					break;
				case "type":
					if (!in_array($value, static::$TYPES))
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					elseif ($this->model->getInline() && ($value == 'Inline' || $value == 'InlineArray'))
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					else
					{
						$this->type = $value;
					}
					break;
				case "stateless":
					if ($value === 'true')
					{
						$this->stateless = true;
					}
					else
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "document-type":
					if (!preg_match('/^[A-Z][A-Za-z0-9]+_[A-Z][A-Za-z0-9]+_[A-Z][A-Za-z0-9]+$/', $value))
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					$this->documentType = $value;
					break;
				case "internal":
					if ($value === 'true')
					{
						$this->internal = true;
					}
					else
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "default-value":
					$this->defaultValue = $value;
					break;
				case "required":
					if ($value === 'true')
					{
						$this->required = true;
					}
					else
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "min-occurs":
					$this->minOccurs = intval($value);
					if ($this->minOccurs <= 1)
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "max-occurs":
					$this->maxOccurs = intval($value);
					if ($this->maxOccurs <= 1)
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "localized":
					if ($value === 'true')
					{
						$this->localized = true;
					}
					else
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				case "has-correction":
					if ($value === 'true')
					{
						$this->hasCorrection = true;
					}
					else
					{
						throw new \RuntimeException('Invalid ' . $name . ' attribute value: ' . $value, 54022);
					}
					break;
				default:
					throw new \RuntimeException('Invalid property attribute ' . $name . ' = ' . $value, 54023);
					break;
			}
		}

		if ($this->name === null)
		{
			throw new \RuntimeException('Property name can not be null', 54024);
		}

		if ($this->stateless && $this->hasCorrection)
		{
			throw new \RuntimeException('Property stateless can not be applicable', 54024);
		}

		if ($this->localized && ($this->type == 'Document' || $this->type == 'DocumentArray'))
		{
			throw new \RuntimeException('Invalid localized attribute on Document property: ' . $this, 54022);
		}
	}

	/**
	 * @param $xmlElement
	 * @param Compiler $compiler
	 * @throws \RuntimeException
	 */
	public function importXmlChildNodes($xmlElement, Compiler $compiler)
	{
		foreach ($xmlElement->childNodes as $node)
		{
			/* @var $node \DOMElement */
			if ($node->nodeName == 'constraint')
			{
				if ($this->constraintArray === null)
				{
					$this->constraintArray = array();
				}
				$params = array();
				$name = null;
				foreach ($node->attributes as $attr)
				{
					/* @var $attr \DOMAttr */
					if ($attr->name === 'name')
					{
						$name = $attr->value;
					}
					else
					{
						$v = $attr->value;
						if ($v === 'true')
						{
							$v = true;
						}
						elseif ($v === 'false')
						{
							$v = false;
						}
						$params[$attr->name] = $v;
					}
				}
				if ($name)
				{
					$this->constraintArray[$name] = $params;
				}
				else
				{
					throw new \RuntimeException('Constraint Name can not be null', 54025);
				}
			}
			elseif ($node->nodeName == 'dboptions' && !$this->stateless)
			{
				if ($this->dbOptions === null)
				{
					$this->dbOptions = array();
				}
				foreach ($node->attributes as $attr)
				{
					/* @var $attr \DOMAttr */
					$this->dbOptions[$attr->name] = $attr->value;
				}
			}
			elseif ($node->nodeName == 'inline')
			{
				if ($this->inlineType)
				{
					throw new \RuntimeException('Duplicate property children node ' . $this->getName() . ' -> '
						. $node->nodeName, 54026);
				}
				$documentName = $this->model->getShortName() . ucfirst($this->getName());
				$model = new Model($this->model->getVendor(), $this->model->getShortModuleName(), $documentName);
				$model->setXmlInlineElement($node, $compiler);
				$this->inlineModel = $model;
				$this->inlineType = $model->getName();
				$compiler->addModel($model);
			}
			elseif ($node->nodeType == XML_ELEMENT_NODE)
			{
				throw new \RuntimeException('Invalid property children node ' . $this->getName() . ' -> '
					. $node->nodeName, 54026);
			}
		}

		if (($this->type === 'Inline' || $this->type === 'InlineArray') && !$this->inlineType)
		{
			throw new \RuntimeException('Inline property required inline document' . $this , 54026);
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function applyDefaultProperties()
	{
		switch ($this->name)
		{
			case 'label':
				if ($this->type !== null)
				{
					$this->type = 'String';
					$this->required = true;
				}
				break;
			case 'title':
				if ($this->type !== null)
				{
					$this->type = 'String';
				}
				break;
			case 'refLCID':
			case 'LCID':
				$this->type = 'String';
				$this->constraintArray['maxSize'] = array('max' => 5);
				$this->dbOptions['length'] = 5;
				$this->required = true;
				break;
			case 'authorName':
				$this->type = 'String';
				$this->defaultValue = 'Anonymous';
				$this->constraintArray['maxSize'] = array('max' => 100);
				break;
			case 'authorId':
				$this->type = 'DocumentId';
				break;
			case 'documentVersion':
				$this->type = 'Integer';
				break;
			case 'publicationStatus':
				$this->type = 'String';
				$this->defaultValue = 'DRAFT';
				$this->required = true;
				break;
			case 'creationDate':
			case 'modificationDate':
				$this->type = 'DateTime';
				$this->defaultValue = 'now';
				break;
			case 'active':
				$this->type = 'Boolean';
				$this->defaultValue = 'true';
				break;
			case 'startPublication':
			case 'endPublication':
			case 'startActivation':
			case 'endActivation':
				$this->type = 'DateTime';
				break;
			case 'versionOfId':
				$this->type = 'DocumentId';
				$this->documentType = $this->model->getName();
				break;
			case 'treeName':
				$this->type = 'String';
				$this->constraintArray['maxSize'] = array('max' => 50);
				$this->dbOptions['length'] = 50;
				break;
		}

		if ($this->type === 'String')
		{
			if (!isset($this->constraintArray['maxSize']))
			{
				$this->constraintArray['maxSize'] = array('max' => 255);
			}

			if ($this->name === 'publicationStatus')
			{
				if (!isset($this->constraintArray['publicationStatus']))
				{
					$this->constraintArray['publicationStatus'] = array();
				}
			}
		}
		elseif ($this->type === 'StorageUri')
		{
			if (!isset($this->constraintArray['storageUri']))
			{
				$this->constraintArray['storageUri'] = array();
			}
		}
	}

	/**
	 * @return Property|null
	 */
	protected function initializeParent()
	{
		$model = $this->getModel();
		$parentModel = $model->getParent();
		while ($parentModel)
		{
			$p = $parentModel->getPropertyByName($this->name);
			if ($p)
			{
				$this->setParent($p);
				return $p;
			}
			$parentModel = $parentModel->getParent();
		}
		return null;
	}
}

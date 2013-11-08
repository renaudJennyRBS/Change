<?php
namespace Rbs\Catalog\Std;

/**
 * @name \Rbs\Catalog\Std\AttributePresentation
 */
class AttributePresentation
{
	/**
	 * @var \Rbs\Catalog\Documents\Product
	 */
	protected $product;

	/**
	 * @var \Rbs\Catalog\Documents\Attribute
	 */
	protected $attribute;

	/**
	 * @var array
	 */
	protected $attributeValues;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 */
	public function __construct(\Rbs\Catalog\Documents\Product $product = null)
	{
		$this->product = $product;
		if ($product)
		{
			$this->attribute = $product->getAttribute();
			$this->attributeValues = $product->getAttributeValues();
		}
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}


	/**
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		return $this->getApplicationServices()->getCollectionManager();
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @return $this
	 */
	public function setAttribute($attribute)
	{
		$this->attribute = $attribute;
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\Documents\Attribute
	 */
	public function getAttribute()
	{
		return $this->attribute;
	}

	/**
	 * @param array $attributeValues
	 * @return $this
	 */
	public function setAttributeValues($attributeValues)
	{
		$this->attributeValues = $attributeValues;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributeValues()
	{
		return $this->attributeValues;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return $this
	 */
	public function setProduct($product)
	{
		$this->product = $product;
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\Documents\Product
	 */
	public function getProduct()
	{
		return $this->product;
	}

	/**
	 * @param string $visibility
	 * @return array
	 */
	public function getConfiguration($visibility)
	{
		if (!$this->attribute || !$this->attributeValues || !$this->attribute->getAttributesCount())
		{
			return array();
		}

		$configuration = array('global' => array('items' => array()));
		foreach ($this->attribute->getAttributes() as $attribute)
		{
			if (!$attribute->isVisibleFor($visibility))
			{
				continue;
			}
			if ($attribute->getAttributesCount())
			{
				$title = $attribute->getCurrentLocalization()->getTitle();
				$configuration[$attribute->getId()] = array('title' => $title, 'items' => $this->generateItems($attribute, $visibility));
			}
			else
			{
				$item = $this->generateItem($attribute);
				if ($item)
				{
					$configuration['global']['items'][$attribute->getId()] = $item;
				}
			}
		}

		if (count($configuration['global']['items']))
		{
			$i18n = $this->applicationServices->getApplicationServices()->getI18nManager();
			$configuration['global']['title'] = $i18n->trans('m.rbs.catalog.fo.main-attributes', array('ucf'));
		}
		else
		{
			unset($configuration['global']);
		}
		return $configuration;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $group
	 * @param string $visibility
	 * @return array
	 */
	protected function generateItems(\Rbs\Catalog\Documents\Attribute $group, $visibility)
	{
		$items = array();
		foreach ($group->getAttributes() as $attribute)
		{
			if (!$attribute->isVisibleFor($visibility))
			{
				continue;
			}
			if ($attribute->getAttributesCount())
			{
				$items = array_merge($items, $this->generateItems($attribute, $visibility));
			}
			else
			{
				$item = $this->generateItem($attribute);
				if ($item)
				{
					$items[$attribute->getId()] = $item;
				}
			}
		}
		return $items;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @return array
	 */
	protected function generateItem(\Rbs\Catalog\Documents\Attribute $attribute)
	{
		$attributeId = $attribute->getId();
		$value = array_reduce($this->attributeValues, function($result, $attrVal) use ($attributeId) {
			return $attributeId == $attrVal['id'] ? $attrVal['value'] : $result;
		});

		$valueType = $attribute->getValueType();
		switch ($valueType)
		{
			case \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY:
				if ($this->product)
				{
					$property = $attribute->getModelProperty();
					if ($property)
					{
						$valueType = $this->getAttributeTypeFromProperty($property);
						$value = $property->getValue($this->product);
					}
				}
				if ($valueType == \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
				{
					$valueType = \Rbs\Catalog\Documents\Attribute::TYPE_TEXT;
					$value = strval($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENT:
				if ($value !== null)
				{
					$value = $attribute->getDocumentManager()->getDocumentInstance($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME:
				if ($value !== null)
				{
					$value = new \DateTime($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_TEXT:
				if ($value !== null)
				{
					$value = new \Change\Documents\RichtextProperty($value);
				}
				break;
		}

		if ($value)
		{
			return $this->renderItem($attribute, $value, $valueType);
		}
		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @param mixed $value
	 * @param string $valueType
	 * @return array
	 */
	protected function renderItem($attribute, $value, $valueType)
	{
		$title = $attribute->getCurrentLocalization()->getTitle();
		$item = array('title' => $title, 'value' => $value, 'valueType' => $valueType);

		$description = $attribute->getCurrentLocalization()->getDescription();
		if ($description && !$description->isEmpty())
		{
			$item['description'] = $description;
		}

		switch ($item['valueType'])
		{
			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENT:
				if (!$this->isValidDocument($value))
				{
					return null;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/document.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTARRAY:
				foreach ($value as $index => $document)
				{
					if (!$this->isValidDocument($document))
					{
						unset($value[$index]);
					}
				}
				if (count($value) < 1)
				{
					return null;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/documentarray.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/datetime.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_BOOLEAN:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/boolean.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_FLOAT:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/float.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_INTEGER:
				$value = $this->getCollectionItemTitle($attribute->getCollectionCode(), $value);
				if ($value !== false)
				{
					$item['value'] = $value;
					$item['template'] = 'Rbs_Catalog/Blocks/Attribute/text.twig';
				}
				else
				{
					$item['template'] = 'Rbs_Catalog/Blocks/Attribute/integer.twig';
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_TEXT:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/richtext.twig';
				break;

			default:
				$value = $this->getCollectionItemTitle($attribute->getCollectionCode(), $value);
				if ($value !== false)
				{
					$item['value'] = $value;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/text.twig';
				break;
		}
		return $item;
	}

	/**
	 * @param $collectionCode
	 * @param $value
	 * @return string|boolean
	 */
	protected function getCollectionItemTitle($collectionCode, $value)
	{
		if (is_string($collectionCode))
		{
			$c = $this->getCollectionManager()->getCollection($collectionCode);
			if ($c)
			{
				$i = $c->getItemByValue($value);
				if ($i)
				{
					return $i->getTitle();
				}
			}
		}
		return false;
	}

	/**
	 * @param \Change\Documents\Property $property
	 * @return string
	 */
	protected function getAttributeTypeFromProperty($property)
	{
		switch ($property->getType())
		{
			case \Change\Documents\Property::TYPE_DOCUMENT :
			case \Change\Documents\Property::TYPE_DOCUMENTID :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENT;
			case \Change\Documents\Property::TYPE_DOCUMENTARRAY :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTARRAY;
			case \Change\Documents\Property::TYPE_STRING :
				return \Rbs\Catalog\Documents\Attribute::TYPE_CODE;
			case \Change\Documents\Property::TYPE_BOOLEAN :
				return \Rbs\Catalog\Documents\Attribute::TYPE_BOOLEAN;
			case \Change\Documents\Property::TYPE_INTEGER :
				return \Rbs\Catalog\Documents\Attribute::TYPE_INTEGER;
			case \Change\Documents\Property::TYPE_FLOAT :
			case \Change\Documents\Property::TYPE_DECIMAL :
				return \Rbs\Catalog\Documents\Attribute::TYPE_FLOAT;
			case \Change\Documents\Property::TYPE_DATE :
			case \Change\Documents\Property::TYPE_DATETIME :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME;
			case \Change\Documents\Property::TYPE_RICHTEXT :
				return \Rbs\Catalog\Documents\Attribute::TYPE_TEXT;
			default:
				return null;
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			return $document->published();
		}
		elseif ($document instanceof \Change\Documents\Interfaces\Activable)
		{
			return $document->activated();
		}
		return true;
	}
}
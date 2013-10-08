<?php
namespace Rbs\Catalog\Documents;

use Change\Documents\AbstractModel;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Rbs\Catalog\Std\AttributeEngine;

/**
 * @name \Rbs\Catalog\Documents\Attribute
 */
class Attribute extends \Compilation\Rbs\Catalog\Documents\Attribute
{
	const TYPE_BOOLEAN = 'Boolean';
	const TYPE_INTEGER = 'Integer';
	const TYPE_DOCUMENT = 'Document';
	const TYPE_DOCUMENTARRAY = 'DocumentArray';
	const TYPE_FLOAT = 'Float';
	const TYPE_DATETIME = 'DateTime';
	const TYPE_CODE = 'Code';
	const TYPE_TEXT = 'Text';
	const TYPE_GROUP = 'Group';
	const TYPE_PROPERTY = 'Property';

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	protected  function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$documentResult->setProperty('editorDefinition', (new AttributeEngine($this->getDocumentServices()))->buildEditorDefinition($this));
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);
		if (in_array('valueTypeFormatted', $extraColumn))
		{
			/* @var $attribute Attribute */
			$fv = $this->getApplicationServices()->getI18nManager()->trans('m.rbs.catalog.documents.attribute.type-' . strtolower($this->getValueType()), array('ucf'));
			$documentLink->setProperty('valueTypeFormatted', $fv);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return Attribute[]|\Change\Documents\AbstractDocument|mixed|null
	 */
	public function getValue(\Rbs\Catalog\Documents\Product $product)
	{
		$vt = $this->getValueType();
		if ($vt === static::TYPE_PROPERTY)
		{
			$property = $this->getModelProperty();
			return ($property) ? $property->getValue($product) : null;
		}
		elseif ($vt === static::TYPE_GROUP)
		{
			return $this->getAttributes()->toArray();
		}
		else
		{
			$values = $product->getAttributeValues();
			if (is_array($values) && count($values))
			{
				foreach ($values as $value)
				{
					if ($value['id'] === $this->getId())
					{
						$value = $value['value'];
						if ($value !== null)
						{
							if ($vt === static::TYPE_DATETIME)
							{
								return new \DateTime($value);
							}
							elseif ($vt === static::TYPE_DOCUMENT)
							{
								return $this->getDocumentManager()->getDocumentInstance($vt);
							}
						}
						return $value;
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param AbstractModel $documentModel
	 */
	public function setDefaultValues(AbstractModel $documentModel)
	{
		parent::setDefaultValues($documentModel);
		$this->setAttributes(array('specifications', 'comparisons'));
	}

	/**
	 * @param string $visibility
	 * @return string
	 */
	public function isVisibleFor($visibility)
	{
		return in_array($visibility, $this->getVisibility());
	}

	/**
	 * @return \Change\Documents\Property|null
	 */
	public function getModelProperty()
	{
		if ($this->getValueType() === static::TYPE_PROPERTY)
		{
			if (strpos($this->getProductProperty(), '::'))
			{
				list($modelName, $propertyName) = explode('::', $this->getProductProperty());
			}
			else
			{
				$modelName = 'Rbs_Catalog_Product';
				$propertyName = $this->getProductProperty();
			}

			$model = $this->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if ($model)
			{
				return $model->getProperty($propertyName);
			}
		}
		return null;
	}


	protected function onCreate()
	{
		if ($this->getValueType() === static::TYPE_GROUP)
		{
			$this->setAxisGroupVisibility();
		}
	}

	protected function onUpdate()
	{
		if ($this->getValueType() === static::TYPE_GROUP && $this->isPropertyModified('attributes'))
		{
			$this->setAxisGroupVisibility();
		}
	}

	protected function setAxisGroupVisibility()
	{
		$axisVisibility = false;
		foreach ($this->getAttributes() as $attribute)
		{
			if ($attribute->isVisibleFor('axes'))
			{
				$axisVisibility = true;
				break;
			}
		}

		$visibility = $this->getVisibility();
		if (!is_array($visibility))
		{
			$visibility = array();
		}

		if ($axisVisibility)
		{
			if (!in_array('axes', $visibility))
			{
				$visibility[] = 'axes';
			}
		}
		else
		{
			$index = array_search('axes', $visibility, true);
			if ($index !== false)
			{
				unset($visibility[$index]);
			}
		}
		$this->setVisibility(count($visibility) ? $visibility : null);
	}
}
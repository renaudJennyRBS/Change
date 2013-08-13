<?php
namespace Rbs\Catalog\Documents;

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
			$fv = $this->getApplicationServices()->getI18nManager()->trans('m.rbs.catalog.document.attribute.type-' . strtolower($this->getValueType()), array('ucf'));
			$documentLink->setProperty('valueTypeFormatted', $fv);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @return Attribute[]|\Change\Documents\AbstractDocument|mixed|null
	 */
	public function getValue(\Rbs\Catalog\Documents\AbstractProduct $product)
	{
		$vt = $this->getValueType();
		if ($vt === static::TYPE_PROPERTY)
		{
			$property = $product->getDocumentModel()->getProperty($this->getProductProperty());
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
}

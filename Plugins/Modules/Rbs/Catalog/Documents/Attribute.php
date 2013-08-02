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

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);

		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentLink)
			{
				$extraColumn = $event->getParam('extraColumn', array());
				if (in_array('valueTypeFormatted', $extraColumn))
				{
					/* @var $attribute Attribute */
					$attribute = $event->getDocument();
					$fv = $attribute->getApplicationServices()->getI18nManager()->trans('m.rbs.catalog.document.attribute.type-' .strtolower($attribute->getValueType()), array('ucf'));
					$result->setProperty('valueTypeFormatted', $fv);
				}
			}
			elseif ($result instanceof DocumentResult)
			{
				/* @var $attribute Attribute */
				$attribute = $event->getDocument();
				$result->setProperty('editorDefinition', (new AttributeEngine($attribute->getDocumentServices()))->buildEditorDefinition($attribute));
			}
		}, 5);
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

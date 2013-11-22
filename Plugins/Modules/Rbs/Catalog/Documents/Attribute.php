<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\Attribute
 */
class Attribute extends \Compilation\Rbs\Catalog\Documents\Attribute
{
	const TYPE_BOOLEAN = 'Boolean';
	const TYPE_INTEGER = 'Integer';
	const TYPE_DOCUMENTID = 'DocumentId';
	const TYPE_DOCUMENTIDARRAY = 'DocumentIdArray';
	const TYPE_FLOAT = 'Float';
	const TYPE_DATETIME = 'DateTime';
	const TYPE_CODE = 'Code';
	const TYPE_TEXT = 'Text';

	//Special type
	const TYPE_GROUP = 'Group';
	const TYPE_PROPERTY = 'Property';

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				/** @var $attribute Attribute */
				$attribute = $event->getDocument();
				$restResult->setProperty('editorDefinition', $cs->getAttributeManager()->buildEditorDefinition($attribute));
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$extraColumn = $event->getParam('extraColumn');
			if (in_array('valueTypeFormatted', $extraColumn))
			{
				/* @var $attribute Attribute */
				$fv = $event->getApplicationServices()->getI18nManager()->trans('m.rbs.catalog.documents.attribute_type_'
					. strtolower($this->getValueType()), array('ucf'));
				$restResult->setProperty('valueTypeFormatted', $fv);
			}
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
			$values = $product->getCurrentLocalization()->getAttributeValues();
			if (is_array($values) && count($values))
			{
				foreach ($values as $value)
				{
					if ($value['id'] != $this->getId())
					{
						continue;
					}
					$value = $value['value'];
					if ($value !== null)
					{
						if ($vt === static::TYPE_DATETIME)
						{
							return new \DateTime($value);
						}
						elseif ($vt === static::TYPE_DOCUMENTID)
						{
							if (is_numeric($value) && $value > 0)
							{
								return $value;
							}
							elseif (is_array($value) && isset($value['id']))
							{
								return $value['id'];
							}
							$value = null;
						}
						elseif ($vt === static::TYPE_DOCUMENTIDARRAY)
						{
							if (is_array($value))
							{
								$ids = array();
								foreach($value as $id)
								{
									if (is_numeric($id) && $id > 0)
									{
										$ids[] = $id;
									}
									elseif (is_array($id) && isset($id['id']))
									{
										$ids[] = $id['id'];
									}
								}
								return count($ids) ? $ids : null;
							}
							$value = null;
						}
					}
					return $value;
				}
			}
		}
		return null;
	}

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function setDefaultValues(\Change\Documents\AbstractModel $documentModel)
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

			$model = $this->getDocumentManager()->getModelManager()->getModelByName($modelName);
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
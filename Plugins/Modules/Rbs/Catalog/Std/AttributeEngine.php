<?php
namespace Rbs\Catalog\Std;

use Rbs\Catalog\Documents\Attribute;

/**
 * @name \Rbs\Catalog\Std\AttributeEngine
 */
class AttributeEngine
{
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	function __construct(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->documentServices->getApplicationServices();
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @return array
	 */
	public function getAttributeValues($product)
	{
		$productId = ($product instanceof \Rbs\Catalog\Documents\Product) ? $product->getId() : intval($product);
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('attribute_id'), $fb->alias($fb->getDocumentColumn('valueType'), 'valueType'),
			$fb->column('integer_value'), $fb->column('date_value'), $fb->column('float_value'),
			$fb->column('string_value'), $fb->column('text_value'));
		$qb->from($fb->table('rbs_catalog_dat_attribute'));
		$qb->innerJoin($fb->getDocumentTable('Rbs_Catalog_Attribute'),
			$fb->eq($fb->getDocumentColumn('id'), $fb->column('product_id')));
		$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		$query = $qb->query();
		$query->bindParameter('productId', $productId);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('attribute_id', 'integer_value')
			->addStrCol('valueType', 'string_value')->addNumCol('float_value')->addDtCol('date_value')->addTxtCol('text_value'));
		$values = array();
		
		foreach ($rows as $row)
		{
			$val = array('id' => $row['attribute_id'], 'valueType' => $row['valueType']);
			switch ($row['valueType'])
			{
				case Attribute::TYPE_BOOLEAN:
					$val['value'] = $row['integer_value'] != 0;
					break;
				case Attribute::TYPE_INTEGER:
				case Attribute::TYPE_DOCUMENT:
					$val['value'] = $row['integer_value'];
					break;
				case Attribute::TYPE_DATETIME:
					/* @var $v \DateTime */
					$v = $row['date_value'];
					$val['value'] = $v->format(\DateTime::ISO8601);
					break;
				case Attribute::TYPE_FLOAT:
					$val['value'] = $row['float_value'];
					break;
				case Attribute::TYPE_CODE:
					$val['value'] = $row['string_value'];
					break;
				case Attribute::TYPE_TEXT:
					$val['value'] = $row['text_value'];
					break;
				default:
					$val['value'] = null;
					break;
			}
			$values[] = $val;
		}
		return $values;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param array $values
	 */
	public function setAttributeValues($product, $values)
	{
		$productId = ($product instanceof \Rbs\Catalog\Documents\Product) ? $product->getId() : intval($product);
		if (is_array($values))
		{
			$defined = $this->getDefinedAttributesValues($productId);
			foreach ($values as $value)
			{
				if ($value['valueType'] === Attribute::TYPE_PROPERTY)
				{
					continue;
				}

				if (isset($defined[$value['id']]))
				{
					$this->updateAttributeValue($defined[$value['id']], $value);
				}
				else
				{
					$defined[$value['id']] = $this->insertAttributeValue($productId, $value);
				}
			}
		}
		else
		{
			$this->deleteAttributeValue($productId);
		}
	}

	/**
	 * @param integer $productId
	 * @return array
	 */
	protected function getDefinedAttributesValues($productId)
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'), $fb->column('attribute_id'));
		$qb->from($fb->table('rbs_catalog_dat_attribute'));
		$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		$query = $qb->query();
		$query->bindParameter('productId', $productId);
		$result = array();
		foreach ($query->getResults($query->getRowsConverter()->addIntCol('id', 'attribute_id')) as $row)
		{
			$result[$row['attribute_id']] = $row['id'];
		}
		return $result;
	}

	protected function dispatchValue($valueType, $value)
	{
		//integer_value, float_value, date_value, string_value, text_value
		$result = array(null, null, null, null, null);
		if ($value !== null)
		{
			switch ($valueType)
			{
				case Attribute::TYPE_BOOLEAN:
					$result[0] = $value ? 1 : 0;
					break;
				case Attribute::TYPE_INTEGER:
				case Attribute::TYPE_DOCUMENT:
					$result[0] = is_array($value) ? intval($value['id']) : intval($value);
					break;
				case Attribute::TYPE_FLOAT:
					$result[1] = $value;
					break;
				case Attribute::TYPE_DATETIME:
					$result[2] = is_string($value) ? new \DateTime($value) : $value;
					break;
				case Attribute::TYPE_CODE:
					$result[3] = $value;
					break;
				case Attribute::TYPE_TEXT:
					$result[4] = $value;
					break;
			}
		}
		return $result;
	}

	/**
	 * @param integer $productId
	 * @param array $value
	 * @return integer
	 */
	protected function insertAttributeValue($productId, $value)
	{
		$valueType = $value['valueType'];
		$attributeId = $value['id'];
		list($integerValue, $floatValue, $dateValue, $stringValue, $textValue) = $this->dispatchValue($valueType,
			$value['value']);
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('insertAttributeValue');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_catalog_dat_attribute'),
				$fb->column('product_id'), $fb->column('attribute_id'),
				$fb->column('integer_value'), $fb->column('float_value'), $fb->column('date_value'),
				$fb->column('string_value'), $fb->column('text_value'));
			$qb->addValues($fb->integerParameter('productId'), $fb->integerParameter('attributeId'),
				$fb->integerParameter('integerValue'), $fb->decimalParameter('floatValue'), $fb->dateTimeParameter('dateValue'),
				$fb->parameter('stringValue'), $fb->lobParameter('textValue'));
		}
		$is = $qb->insertQuery();
		$is->bindParameter('productId', $productId)->bindParameter('attributeId', $attributeId)
			->bindParameter('integerValue', $integerValue)->bindParameter('floatValue', $floatValue)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('stringValue', $stringValue)->bindParameter('textValue', $textValue);
		$is->execute();
		return $is->getDbProvider()->getLastInsertId('rbs_catalog_dat_attribute');
	}

	/**
	 * @param integer $attrId
	 * @param array $value
	 * @return integer
	 */
	protected function updateAttributeValue($attrId, $value)
	{
		$valueType = $value['valueType'];
		list($integerValue, $floatValue, $dateValue, $stringValue, $textValue) = $this->dispatchValue($valueType,
			$value['value']);
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('updateAttributeValue');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_catalog_dat_attribute'));
			$qb->assign($fb->column('integer_value'), $fb->integerParameter('integerValue'));
			$qb->assign($fb->column('float_value'), $fb->decimalParameter('floatValue'));
			$qb->assign($fb->column('date_value'), $fb->dateTimeParameter('dateValue'));
			$qb->assign($fb->column('string_value'), $fb->parameter('stringValue'));
			$qb->assign($fb->column('text_value'), $fb->lobParameter('textValue'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('attrId')));
		}
		$uq = $qb->updateQuery();
		$uq->bindParameter('integerValue', $integerValue)->bindParameter('floatValue', $floatValue)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('stringValue', $stringValue)->bindParameter('textValue', $textValue)
			->bindParameter('attrId', $attrId);
		$uq->execute();
	}

	/**
	 * @param integer $productId
	 * @param array $excludeAttrIds
	 */
	protected function deleteAttributeValue($productId, $excludeAttrIds = array())
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->delete($fb->table('rbs_catalog_dat_attribute'));
		if (count($excludeAttrIds))
		{
			$notIn = array();
			foreach ($excludeAttrIds as $id)
			{
				$notIn[] = $fb->number($id);
			}
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->notIn($fb->column('id'), $notIn)
			));
		}
		else
		{
			$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		}
		$dq = $qb->deleteQuery();
		$dq->bindParameter('productId', $productId);
		$dq->execute();
	}

	/**
	 * @param Attribute $attribute
	 * @return array|null
	 */
	public function buildEditorDefinition(Attribute $attribute)
	{
		if ($attribute->getValueType() === Attribute::TYPE_GROUP)
		{
			$definition = array('attributes' => array());
			$ids = array($attribute->getId());
			foreach ($attribute->getAttributes() as $childAttribute)
			{
				$ids[] = $childAttribute->getId();
				if ($childAttribute->getValueType() === Attribute::TYPE_GROUP)
				{
					$defGroup = $this->buildGroupDefinition($childAttribute, $ids);
					if (count($defGroup['attributes']))
					{
						$definition['attributes'][] = $defGroup;
					}
				}
				else
				{
					$def = $this->buildAttributeDefinition($childAttribute);
					if ($def)
					{
						$definition['attributes'][] = $def;
					}
				}
			}
			if (count($definition['attributes']))
			{
				$definition['ids'] = $ids;
				return $definition;
			}
		}
		return null;
	}

	/**
	 * @param Attribute $attribute
	 * @param $ids
	 * @return array
	 */
	protected function buildGroupDefinition($attribute, &$ids)
	{
		$definition = array('label' => $attribute->getLabel(), 'attributes' => array());
		foreach ($attribute->getAttributes() as $childAttribute)
		{
			if (!in_array($childAttribute->getId(), $ids))
			{
				$ids[] = $childAttribute->getId();
				if ($childAttribute->getValueType() === Attribute::TYPE_GROUP)
				{
					$groupDef = $this->buildGroupDefinition($childAttribute, $ids);
					$definition['attributes'] = array_merge($definition['attributes'], $groupDef['attributes']);
				}
				else
				{
					$def = $this->buildAttributeDefinition($childAttribute);
					if ($def)
					{
						$definition['attributes'][] = $def;
					}
				}
			}
		}
		return $definition;
	}

	/**
	 * @param Attribute $attribute
	 * @return array|null
	 */
	protected function buildAttributeDefinition($attribute)
	{
		$vt = $attribute->getValueType();
		$definition = array('id' => $attribute->getId(), 'label' => $attribute->getLabel(),
			'required' => $attribute->getRequiredValue(), 'valueType' => $vt, 'type' => $vt,
			'defaultValue' => null, 'collectionCode' => null);

		if (Attribute::TYPE_PROPERTY == $vt)
		{
			if (strpos($attribute->getProductProperty(), '::'))
			{
				list($modelName, $propertyName) = explode('::', $attribute->getProductProperty());
			}
			else
			{
				$modelName = 'Rbs_Catalog_Product';
				$propertyName = $attribute->getProductProperty();
			}
			$model = $this->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				return null;
			}
			$property = $model->getProperty($propertyName);
			if (!$property || !$property->getType())
			{
				return null;
			}

			switch ($property->getType())
			{
				case \Change\Documents\Property::TYPE_DOCUMENT :
				case \Change\Documents\Property::TYPE_DOCUMENTID :
					$definition['type'] = Attribute::TYPE_DOCUMENT;
					$definition['documentType'] = ($property->getDocumentType()) ? $property->getDocumentType() : '';
					break;
				case \Change\Documents\Property::TYPE_DOCUMENTARRAY :
					$definition['type'] = Attribute::TYPE_DOCUMENTARRAY;
					$definition['documentType'] = ($property->getDocumentType()) ? $property->getDocumentType() : '';
					break;
				case \Change\Documents\Property::TYPE_STRING :
					$definition['type'] = Attribute::TYPE_CODE;
					break;
				case \Change\Documents\Property::TYPE_BOOLEAN :
					$definition['type'] = Attribute::TYPE_BOOLEAN;
					break;
				case \Change\Documents\Property::TYPE_INTEGER :
					$definition['type'] = Attribute::TYPE_INTEGER;
					break;
				case \Change\Documents\Property::TYPE_FLOAT :
				case \Change\Documents\Property::TYPE_DECIMAL :
					$definition['type'] = Attribute::TYPE_FLOAT;
					break;
				case \Change\Documents\Property::TYPE_DATE :
				case \Change\Documents\Property::TYPE_DATETIME :
					$definition['type'] = Attribute::TYPE_DATETIME;
					break;
				case \Change\Documents\Property::TYPE_RICHTEXT :
					$definition['type'] = Attribute::TYPE_TEXT;
					break;
				default:
					return null;
			}
		}

		if (($dv = $attribute->getDefaultValue()) !== null)
		{
			if ($vt === Attribute::TYPE_BOOLEAN)
			{
				$definition['defaultValue'] = ($dv == '1' || $dv == 'true');
			}
			elseif ($vt === Attribute::TYPE_INTEGER)
			{
				$definition['defaultValue'] = intval($dv);
			}
			elseif ($vt === Attribute::TYPE_FLOAT)
			{
				$definition['defaultValue'] = floatval($dv);
			}
			elseif ($vt === Attribute::TYPE_DATETIME)
			{
				$definition['defaultValue'] = (new \DateTime($dv))->format(\DateTime::ISO8601);
			}
			elseif ($vt === Attribute::TYPE_CODE)
			{
				$definition['defaultValue'] = $dv;
			}
		}

		if (in_array($vt, array(Attribute::TYPE_INTEGER, Attribute::TYPE_CODE, Attribute::TYPE_DOCUMENT)))
		{
			$definition['collectionCode'] = $attribute->getCollectionCode();
		}
		return $definition;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @return null
	 * @return array
	 */
	public function normalizeAttributeValues(\Rbs\Catalog\Documents\Product $product, $attributeValues)
	{
		$normalizedValues = array();
		if (is_array($attributeValues) && count($attributeValues))
		{
			$utcTimeZone = new \DateTimeZone('UTC');
			$documentManager = $this->getDocumentServices()->getDocumentManager();
			foreach ($attributeValues as $attributeValue)
			{
				$id = intval($attributeValue['id']);
				$attribute = $documentManager->getDocumentInstance($id);
				if (!$attribute instanceof Attribute)
				{
					continue;
				}
				$valueType = $attribute->getValueType();
				$value = isset($attributeValue['value']) ? $attributeValue['value'] : null;
				switch ($valueType)
				{
					case Attribute::TYPE_PROPERTY:
						$attribute = $documentManager->getDocumentInstance($id);
						if ($attribute instanceof Attribute)
						{
							$property = $product->getDocumentModel()->getProperty($attribute->getProductProperty());
							if ($property)
							{
								$pc = new \Change\Http\Rest\PropertyConverter($product, $property);
								$pc->setPropertyValue($value);
								$value = $pc->getRestValue();
							}
						}
						break;
					case Attribute::TYPE_DOCUMENT:
						if (is_array($value) && isset($value['id']))
						{
							$value = $value['id'];
						}
						elseif (is_numeric($value))
						{
							$value = intval($value);
						}
						else
						{
							$value = null;
						}
						break;
					case Attribute::TYPE_DATETIME:
						$value = (new \DateTime($value, $utcTimeZone))->format(\DateTime::ISO8601);
						break;
				}
				$normalizedValues[] = array('id' => $id, 'valueType' => $valueType, 'value' => $value);
			}
		}
		if (count($normalizedValues))
		{

			return $normalizedValues;
		}
		else
		{
			$this->deleteAttributeValue($product->getId());
			return null;
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function expandAttributeValues($product, $attributeValues, $urlManager)
	{
		$expandedAttributeValues = array();
		if (is_array($attributeValues) && count($attributeValues))
		{
			$documentManager = $this->getDocumentServices()->getDocumentManager();
			$valueConverter = new \Change\Http\Rest\ValueConverter($urlManager, $documentManager);
			foreach ($attributeValues as  $attributeValue)
			{
				$id = intval($attributeValue['id']);
				$attribute = $documentManager->getDocumentInstance($id);
				if (!$attribute instanceof Attribute)
				{
					continue;
				}

				$valueType = $attribute->getValueType();
				$value = $attributeValue['value'];

				switch ($valueType)
				{
					case Attribute::TYPE_PROPERTY:
						$attribute = $documentManager->getDocumentInstance($id);
						if ($attribute instanceof Attribute)
						{
							$property = $product->getDocumentModel()->getProperty($attribute->getProductProperty());
							if ($property)
							{
								$pc = new \Change\Http\Rest\PropertyConverter($product, $property, $urlManager);
								$value = $pc->getRestValue();
							}
						}
						break;

					case Attribute::TYPE_DOCUMENT:
						if ($value !== null)
						{
							$document = $documentManager->getDocumentInstance($value);
							$value = $valueConverter->toRestValue($document, \Change\Documents\Property::TYPE_DOCUMENT);
						}
						break;
					case Attribute::TYPE_DATETIME:
						if ($value !== null)
						{
							$value = $valueConverter->toRestValue(new \DateTime($value), \Change\Documents\Property::TYPE_DATETIME);
						}
						break;
				}
				$expandedAttributeValues[] = array('id' => $id, 'valueType' => $valueType, 'value' => $value);
			}
		}
		return count($expandedAttributeValues) ? $expandedAttributeValues : null;
	}
}
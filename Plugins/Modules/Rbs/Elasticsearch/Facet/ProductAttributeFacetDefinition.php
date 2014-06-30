<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
* @name \Rbs\Elasticsearch\Facet\ProductAttributeFacetDefinition
*/
class ProductAttributeFacetDefinition extends \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
{
	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Rbs\Catalog\Documents\Attribute|boolean|null
	 */
	protected $attribute = false;

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facet
	 */
	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		parent::__construct($facet);
		$this->mappingName = $this->parameters->get(static::PARAM_MAPPING_NAME);
		if (!$this->mappingName)
		{
			$this->mappingName = 'a_' . $this->getAttributeId();
		}
	}

	/**
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @return $this
	 */
	public function setCatalogManager($catalogManager)
	{
		$this->catalogManager = $catalogManager;
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\CatalogManager
	 */
	protected function getCatalogManager()
	{
		return $this->catalogManager;
	}

	/**
	 * @return integer
	 */
	protected function getAttributeId()
	{
		return $this->getParameters()->get('attributeId', 0);
	}

	/**
	 * @return \Rbs\Catalog\Documents\Attribute|null
	 */
	protected function getAttribute()
	{
		return $this->getDocumentManager()->getDocumentInstance($this->getAttributeId(), 'Rbs_Catalog_Attribute');
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array|void
	 */
	public function addIndexData($document, array $documentData)
	{
		$mappingName = $this->getMappingName();
		if (!array_key_exists($mappingName, $documentData) && $document instanceof \Rbs\Catalog\Documents\Product)
		{
			$attribute = $this->getAttribute();
			if ($attribute)
			{
				$descendants = [];
				if (!$document->getSku())
				{
					if ($document->getVariantGroup())
					{
						$catalogManager = $this->getCatalogManager();
						$descendants = $catalogManager->getProductDescendants($document, true);
					}
					elseif ($document->getProductSet())
					{
						$descendants = $document->getProductSet()->getProducts()->toArray();
					}
				}
				$descendants[] = $document;
				$values = array();
				foreach ($descendants as $descendant)
				{
					$attributeValue = $attribute->getValue($descendant);
					if ($attributeValue instanceof \Change\Documents\AbstractDocument)
					{
						if (!in_array($attributeValue->getId(), $values))
						{
							$values[] = $attributeValue->getId();
						}
					}
					elseif ($attributeValue instanceof \Change\Documents\DocumentArrayProperty)
					{
						foreach ($attributeValue->getIds() as $id)
						{
							if (!in_array($id, $values))
							{
								$values[] = $id;
							}
						}
					}
					elseif ($attributeValue instanceof \DateTime)
					{
						$date = $attributeValue->format(\DateTime::ISO8601);
						if (!in_array($date, $values))
						{
							$values[] = $date;
						}
					}
					elseif (is_string($attributeValue) || is_bool($attributeValue) || is_numeric($attributeValue))
					{
						if (!in_array($attributeValue, $values))
						{
							$values[] = $attributeValue;
						}
					}
				}
				$documentData[$mappingName] = $values;
			}
		}
		return $documentData;
	}

	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		return  ['attributeId' => null, 'mappingName' => null, 'collectionId' => null,
			'showEmptyItem' => false, 'multipleChoice' => true, 'documentId' => false];
	}

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facet
	 */
	public function validateConfiguration($facet)
	{
		$facet->setIndexCategory('store');
		$validParameters = $this->getDefaultParameters();
		$currentParameters = $facet->getParameters();

		/** @var $attribute \Rbs\Catalog\Documents\Attribute */
		$attribute = null;
		foreach ($currentParameters as $name => $value)
		{
			switch ($name) {
				case 'attributeId':
					if ($value) {
						$attribute = $this->getDocumentManager()->getDocumentInstance($value, 'Rbs_Catalog_Attribute');
						if ($attribute)
						{
							$validParameters[$name] = $attribute->getId();
						}
					}
					break;
				case 'collectionId':
					if ($value)
					{
						$coll = $this->getDocumentManager()->getDocumentInstance($value, 'Rbs_Collection_Collection');
						if ($coll)
						{
							$validParameters[$name] = $coll->getId();
						}
					}
					break;
				case 'mappingName':
					if ($value)
					{
						$value = preg_replace('/[^a-zA-Z0-9_]/', '_', trim($value));
						if (!empty($value))
						{
							$validParameters[$name] = $value;
						}
					}
					break;
				case 'showEmptyItem':
				case 'multipleChoice':
					$validParameters[$name] = $value === 'false' ? false : boolval($value);
					break;
			}
		}
		if ($attribute)
		{
			if (!isset($validParameters['collectionId']) && $attribute->getCollectionCode())
			{
				$coll = $this->getCollectionByCode($attribute->getCollectionCode());
				if ($coll)
				{
					$validParameters['collectionId'] = $coll->getId();
				}
			}

			$checkDocumentType = $attribute->getValueType();
			if ($checkDocumentType == \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
			{
				$property = $attribute->getModelProperty();
				if ($property)
				{
					$checkDocumentType = $property->getType();
				}
			}
			if ($checkDocumentType == 'DocumentId' || $checkDocumentType == 'Document') {
				$validParameters['documentId'] = true;
			}
		}
		else
		{
			$validParameters['documentId'] = false;
		}
		$facet->getParameters()->fromArray($validParameters);
	}

	/**
	 * @param array $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$collectionId = $this->getParameters()->get('thresholdCollectionId');
		$documentId = $this->getParameters()->get('documentId');
		if ($documentId && !$collectionId)
		{
			$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
			$mappingName = $this->getMappingName();
			if (isset($aggregations[$mappingName]['buckets']))
			{
				$buckets = $aggregations[$mappingName]['buckets'];
				foreach ($buckets as $bucket)
				{
					$id = intval($bucket['key']);
					$title = null;
					$document = $this->getDocumentManager()->getDocumentInstance($id);
					if ($document && $document->getDocumentModel()->isPublishable())
					{
						$title = $document->getDocumentModel()->getPropertyValue($document, 'title');
					}
					$v = new \Rbs\Elasticsearch\Facet\AggregationValue($bucket['key'], $bucket['doc_count'], $title);
					$av->addValue($v);
					$this->formatChildren($v, $bucket);
				}
			}
			return $av;
		}
		return parent::formatAggregation($aggregations);
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

use Change\Documents\Property;

/**
 * @name \Rbs\Elasticsearch\Facet\FacetManager
 */
class FacetManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Elasticsearch_FacetManager';

	/**
	 * @var array
	 */
	protected $collections = array();

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	protected function getCollectionManager()
	{
		return $this->collectionManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Elasticsearch/Events/FacetManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getIndexerValue', array($this, 'onDefaultGetIndexerValue'), 5);
		$eventManager->attach('getFacetMapping', array($this, 'onDefaultGetFacetMapping'), 5);
		$eventManager->attach('getFacetQuery', array($this, 'onDefaultGetFacetQuery'), 5);
		$eventManager->attach('getFilterQuery', array($this, 'onDefaultGetFilterQuery'), 5);
	}

	/**
	 * @param string $collectionCode
	 * @return \Change\Collection\CollectionInterface|null
	 */
	protected function getCollectionByCode($collectionCode)
	{
		if (!$collectionCode)
		{
			return null;
		}
		if (!array_key_exists($collectionCode, $this->collections))
		{
			$this->collections[$collectionCode] = $this->getCollectionManager()->getCollection($collectionCode);
		}
		return $this->collections[$collectionCode];
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array<fieldName => value>
	 */
	public function getIndexerValues(\Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition, \Change\Documents\AbstractDocument $document)
	{
		$values = array();
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			foreach($indexDefinition->getFacetsDefinition() as $facet)
			{
				if ($facet instanceof FacetDefinitionInterface)
				{
					$value = $this->getIndexerValue($facet, $indexDefinition, $document, $values);
					if (is_array($value))
					{
						$values = array_merge($values, $value);
					}
				}
			}
		}
		return $values;
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @return array
	 */
	public function getIndexMapping(\Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition)
	{
		$indexMapping = array();
		foreach($indexDefinition->getFacetsDefinition() as $facet)
		{
			if ($facet instanceof FacetDefinitionInterface)
			{
				$value = $this->getFacetMapping($facet, $indexDefinition, $indexMapping);
				if (is_array($value))
				{
					$indexMapping = array_merge($indexMapping, $value);
				}
			}
		}
		return $indexMapping;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @param array $indexMapping
	 * @return array|null
	 */
	public function getFacetMapping(FacetDefinitionInterface $facet, $indexDefinition, $indexMapping)
	{
		$parameters = array('facet' => $facet, 'indexDefinition' => $indexDefinition, 'indexMapping' => $indexMapping);
		$event = new \Zend\EventManager\Event('getFacetMapping', $this, $parameters);
		$this->getEventManager()->trigger($event);
		$value = $event->getParam('mapping');
		return is_array($value) ? $value : null;
	}


	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onDefaultGetFacetMapping(\Zend\EventManager\Event $event)
	{
		/* @var $facetManager FacetManager */
		$mapping = null;
		$facet = $event->getParam('facet');
		$indexMapping = $event->getParam('indexMapping');

		$indexDefinition = $event->getParam('indexDefinition');
		if ($indexDefinition instanceof \Rbs\Elasticsearch\Documents\StoreIndex &&
			$facet instanceof \Rbs\Elasticsearch\Documents\Facet)
		{
			$fieldName = $facet->getFieldName();
			//only one indexation by fieldName
			if (array_key_exists($fieldName, $indexMapping))
			{
				return;
			}

			switch ($facet->getValuesExtractorName())
			{
				case 'Price':
					//Mapping included in store index mapping 'prices'
					break;
				case 'SkuThreshold':
					$mapping = array($fieldName => array('type' => 'string', 'index' => 'not_analyzed'),
						$fieldName . '_level' => array('type' => 'long'), 'sku_id' => array('type' => 'long'));
					break;
				case 'Attribute':
					$attribute = $facet->getAttributeIdInstance();
					if ($attribute)
					{
						$type = $attribute->getValueType();
						if ($type === \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
						{
							$property = $attribute->getModelProperty();
							if ($property)
							{
								$type = $property->getType();
							}
						}
						switch ($type)
						{
							case \Rbs\Catalog\Documents\Attribute::TYPE_BOOLEAN:
							case \Change\Documents\Property::TYPE_BOOLEAN:
								$mapping = array($fieldName => array('type' => 'boolean'));
								break;
							case \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME:
							case \Change\Documents\Property::TYPE_DATE:
							case \Change\Documents\Property::TYPE_DATETIME:
								$mapping = array($fieldName => array('type' => 'date'));
								break;
							case \Rbs\Catalog\Documents\Attribute::TYPE_FLOAT:
							case \Change\Documents\Property::TYPE_FLOAT:
							case \Change\Documents\Property::TYPE_DECIMAL:
								$mapping = array($fieldName => array('type' => 'double'));
								break;
							case \Rbs\Catalog\Documents\Attribute::TYPE_INTEGER:
							case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID:
							case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY:
							case \Change\Documents\Property::TYPE_INTEGER:
							case \Change\Documents\Property::TYPE_DOCUMENTID:
							case \Change\Documents\Property::TYPE_DOCUMENT:
							case \Change\Documents\Property::TYPE_DOCUMENTARRAY:
								$mapping = array($fieldName => array('type' => 'long'));
								break;
							default:
								$mapping = array($fieldName => array('type' => 'string', 'index' => 'not_analyzed'));
								break;
						}
					}
			}
		}

		if (is_array($mapping))
		{
			$event->setParam('mapping', $mapping);
		}
	}

	/**
	 * @param FacetDefinitionInterface $facet
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $values
	 * @return array|null
	 */
	public function getIndexerValue($facet, $indexDefinition, $document, $values)
	{
		$parameters = array('facet' => $facet, 'document' => $document,
			'indexDefinition' => $indexDefinition, 'values' => $values);
		$event = new \Change\Events\Event('getIndexerValue', $this, $parameters);
		$this->getEventManager()->trigger($event);
		$value = $event->getParam('value');
		return is_array($value) ? $value : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetIndexerValue(\Change\Events\Event $event)
	{
		/* @var $facetManager FacetManager */
		$value = null;
		$facet = $event->getParam('facet');
		$document = $event->getParam('document');
		$values = $event->getParam('values');

		$indexDefinition = $event->getParam('indexDefinition');
		if ($indexDefinition instanceof \Rbs\Elasticsearch\Documents\StoreIndex &&
			$facet instanceof \Rbs\Elasticsearch\Documents\Facet &&
			$document instanceof \Rbs\Catalog\Documents\Product)
		{
			//only one indexation by fieldName
			if (array_key_exists($facet->getFieldName(), $values))
			{
				return;
			}

			switch ($facet->getValuesExtractorName())
			{
				case 'Price':
					if (isset($values['prices']))
					{
						break;
					}

					$commerceServices = $indexDefinition->getCommerceServices();

					$skus = array();
					if (!$document->getSku() && $document->getVariantGroup())
					{
						$skus = $commerceServices->getCatalogManager()->getAllSku($document, true);
					}
					else
					{
						$skus[] = $document->getSku();
					}

					$store = $indexDefinition->getStore();
					$prices = [];
					if (count($skus) > 0 && $store && $commerceServices)
					{
						$priceManager = $commerceServices->getPriceManager();
						$q = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Price');
						$q->andPredicates($q->eq('active', true), $q->in('sku', $skus), $q->eq('webStore', $store), $q->eq('targetId', 0));
						$q->addOrder('billingArea');
						$q->addOrder('priority', false);
						if (count($skus) > 1)
						{
							$q->addOrder('value', true);
						}
						$q->addOrder('startActivation', false);

						$billingAreaId = null;
						$startActivation = null;
						$zones = null;
						$now = new \DateTime();

						/** @var $price \Rbs\Price\Documents\Price */
						foreach ($q->getDocuments() as $price)
						{
							$billingArea = $price->getBillingArea();
							if (!$billingArea || !$price->getStartActivation()) {
								continue;
							}

							if ($billingAreaId != $price->getBillingAreaId())
							{
								$billingAreaId = $price->getBillingAreaId();
								$endActivation = $price->getEndActivation();
								if (!($endActivation instanceof \DateTime))
								{
									$endActivation = (new \DateTime())->add(new \DateInterval('P10Y'));
								}

								$zones = [];
								foreach ($billingArea->getTaxes() as $tax)
								{
									$zones = array_merge($zones, $tax->getZoneCodes());
								}
								$zones = array_unique($zones);
							}
							else
							{
								$endActivation = $startActivation;
							}

							if ($endActivation < $now)
							{
								continue;
							}
							$startActivation = $price->getStartActivation();

							$priceData = ['priceId' => $price->getId(), 'billingAreaId' => $billingAreaId,
								'startActivation' => $startActivation->format(\DateTime::ISO8601),
								'endActivation' => $endActivation->format(\DateTime::ISO8601),
								'zone' => '', 'value' => $price->getValue()];
							$prices[] = $priceData;

							foreach ($zones as $zone)
							{
								$taxes = $priceManager->getTaxByValue($price->getValue(), $price->getTaxCategories(), $billingArea, $zone);
								$priceZone = $priceData;
								$priceZone['zone'] = $zone;
								$priceZone['valueWithTax'] = $priceManager->getValueWithTax($price->getValue(), $taxes);
								$prices[] = $priceZone;
							}
						}
					}
					$value = ['prices' => $prices];
					break;
				case 'SkuThreshold':

					$skus = array();
					if (!$document->getSku() && $document->getVariantGroup())
					{
						$skus = $indexDefinition->getCommerceServices()->getCatalogManager()->getAllSku($document, true);
					}
					else
					{
						$skus[] = $document->getSku();
					}

					$store = $indexDefinition->getCommerceServices()->getContext()->getWebStore();
					if (count($skus) > 0 && $store)
					{
						$skuId = 0;

						$fieldName = $facet->getFieldName();
						$stockManager = $indexDefinition->getCommerceServices()->getStockManager();

						if (count($skus) == 1)
						{
							$skuId = $skus[0]->getId();
							$level = $stockManager->getInventoryLevel($skus[0], $store);
							$threshold = $stockManager->getInventoryThreshold($skus[0], $store, $level);
						}
						else
						{
							$level = $stockManager->getInventoryLevelForManySku($skus, $store);
							$threshold = $stockManager->getInventoryThresholdForManySku($skus, $store, $level);
						}

						$value = array($fieldName => $threshold, 'sku_id' => $skuId,
							$fieldName . '_level' => $level);
					}
					break;
				case 'Attribute':
					$attribute = $facet->getAttributeIdInstance();
					if ($attribute)
					{
						$fieldName = $facet->getFieldName();

						$descendants = array();
						if (!$document->getSku() && $document->getVariantGroup())
						{
							// Get published variant
							/** @var $commerceServices \Rbs\Commerce\CommerceServices */
							$commerceServices = $event->getServices('commerceServices');
							$catalogManager = $commerceServices->getCatalogManager();
							$descendants = $catalogManager->getProductDescendants($document, true);
						}

						$descendants[] = $document;
						$values = array();
						foreach($descendants as $descendant)
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
							elseif (is_string($attributeValue) || is_bool($attributeValue) || is_numeric($attributeValue) || is_array($attributeValue))
							{
								if (!in_array($attributeValue, $values))
								{
									$values[] = $attributeValue;
								}
							}
						}

						$value = array($fieldName => $values);

					}
			}
		}

		if (is_array($value))
		{
			$event->setParam('value', $value);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @param array|null $facetResult
	 * @param array|null $facetFilter
	 * @return \Rbs\Elasticsearch\Facet\FacetValue[]
	 */
	public function buildFacetValues($facet, $facetResult, $facetFilter)
	{
		$facetValues = [];
		$facetValueFiltered = false;
		$showEmptyItem = $facet->getShowEmptyItem();
		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_TERM)
		{
			$terms = (is_array($facetResult) && isset($facetResult['terms'])) ? $facetResult['terms'] : [];
			if ($showEmptyItem)
			{
				if ($facet instanceof \Rbs\Elasticsearch\Documents\Facet)
				{
					$collection = $this->getCollectionByCode($facet->getCollectionCode());
					if ($collection)
					{
						foreach ($collection->getItems() as $item)
						{
							$itemValue = $item->getValue();
							$add = true;
							foreach ($terms as $term)
							{
								if ($term['term'] == $itemValue) {
									$add = false;
									break;
								}
							}
							if ($add) {
								$terms[] = ['term' => $itemValue, 'count' => 0];
							}
						}
					}
				}
			}

			foreach ($terms as $term)
			{
				$count = intval($term['count']);
				$value = $term['term'];
				$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
				$facetValue->setCount($count);
				if ($facetFilter !== null)
				{
					if ((is_array($facetFilter) && in_array($value, $facetFilter))
						|| (is_string($facetFilter) && $facetFilter == $value)
					)
					{
						$facetValue->setFiltered(true);
						$facetValueFiltered = true;
					}
				}
				$facetValues[] = $this->updateFacetValueTitle($facetValue, $facet);
			}
		}
		elseif($facetResult)
		{
			foreach ($facetResult['ranges'] as $range)
			{
				if (($count = intval($range['count'])) == 0 && !$showEmptyItem)
				{
					continue;
				}

				$value = (isset($range['from']) ? $range['from'] : '') . '::' . (isset($range['to']) ? $range['to'] : '');
				$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
				$facetValue->setCount(intval($range['count']));
				if ($facetFilter !== null)
				{
					if ((is_array($facetFilter) && in_array($value, $facetFilter))
						|| (is_string($facetFilter) && $facetFilter == $value)
					)
					{
						$facetValue->setFiltered(true);
						$facetValueFiltered = true;
					}
				}
				$facetValues[] = $this->updateFacetValueTitle($facetValue, $facet);
			}
		}

		if (!$facet->getParameters()->get(FacetDefinitionInterface::PARAM_MULTIPLE_CHOICE))
		{
			$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue('');
			$facetValue->setValueTitle($this->getI18nManager()->trans('m.rbs.elasticsearch.front.ignore_facet', ['ucf']));
			if (!$facetValueFiltered)
			{
				$facetValue->setFiltered(true);
			}
			$facetValues[] = $facetValue;
		}
		return $facetValues;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetValue $facetValue
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @return \Rbs\Elasticsearch\Facet\FacetValue
	 */
	protected function updateFacetValueTitle($facetValue, $facet)
	{
		if ($facet instanceof \Rbs\Elasticsearch\Documents\Facet)
		{
			$collection = $this->getCollectionByCode($facet->getCollectionCode());
			if ($collection)
			{
				$item = $collection->getItemByValue($facetValue->getValue());
				if ($item)
				{
					$facetValue->setValueTitle($item->getTitle());
				}
			}
			elseif (($attribute = $facet->getAttributeIdInstance()) !== null)
			{
				$attrType = $attribute->getValueType();
				if ($attrType == \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
				{
					$p = $attribute->getModelProperty();
					if ($p && in_array($p->getType(), [Property::TYPE_DOCUMENTID, Property::TYPE_DOCUMENT, Property::TYPE_DOCUMENTARRAY]))
					{
						$attrType = \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID;
					}
				}

				$collection = $this->getCollectionByCode($attribute->getCollectionCode());
				if ($collection)
				{
					$item = $collection->getItemByValue($facetValue->getValue());
					if ($item)
					{
						$facetValue->setValueTitle($item->getTitle());
						return $facetValue;
					}
				}

				if (in_array($attrType, [\Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID, \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY]))
				{
					$document = $this->getDocumentManager()->getDocumentInstance($facetValue->getValue());
					if ($document)
					{
						$valueTitle = $document->getDocumentModel()->getPropertyValue($document, 'title');
						if ($valueTitle !== null)
						{
							$facetValue->setValueTitle($valueTitle);
						}
					}
				}
			}
		}
		elseif ($facet instanceof ModelFacetDefinition)
		{
			$model = $this->getDocumentManager()->getModelManager()->getModelByName($facetValue->getValue());
			if ($model)
			{
				$facetValue->setValueTitle($this->getI18nManager()->trans($model->getLabelKey()));
			}
		}
		return $facetValue;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @return \Elastica\Facet\Terms|\Elastica\Facet\Range|null
	 */
	public function getFacetQuery($facet)
	{
		$parameters = array('facet' => $facet);
		$event = new \Change\Events\Event('getFacetQuery', $this, $parameters);
		$this->getEventManager()->trigger($event);
		return $event->getParam('facetQuery');
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFacetQuery(\Change\Events\Event $event)
	{
		/** @var $facet \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|\Rbs\Elasticsearch\Documents\Facet */
		$facet = $event->getParam('facet');
		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_RANGE &&
			($facet instanceof \Rbs\Elasticsearch\Documents\Facet))
		{
			$extractorName = $facet->getValuesExtractorName();
			$collection = $this->getCollectionByCode($facet->getCollectionCode());
			if (!$collection)
			{
				return;
			}

			$ranges = array();
			foreach ($collection->getItems() as $item)
			{
				$fromTo = explode('::', $item->getValue());
				if (count($fromTo) == 2)
				{
					$ranges[] = $fromTo;
				}
			}

			if (count($ranges))
			{
				$queryFacet = new \Elastica\Facet\Range($facet->getFieldName());
				if ($extractorName == 'Price')
				{
					$cs = $event->getServices('commerceServices');
					if ($cs instanceof \Rbs\Commerce\CommerceServices && $cs->getContext()->getBillingArea())
					{
						$billingArea = $cs->getContext()->getBillingArea();
						$zone = $cs->getContext()->getZone();
						$now = (new \DateTime())->format(\DateTime::ISO8601);

						$queryFacet->setNested('prices');
						$queryFacet->setField($zone ? 'prices.valueWithTax' : 'prices.value');
						$bool = new \Elastica\Filter\Bool();
						$bool->addMust(new \Elastica\Filter\Term(['prices.billingAreaId' => $billingArea->getId()]));
						$bool->addMust(new \Elastica\Filter\Term(['prices.zone' => $zone ? $zone : '']));
						$bool->addMust(new \Elastica\Filter\Range('prices.startActivation', array('lte' => $now)));
						$bool->addMust(new \Elastica\Filter\Range('prices.endActivation', array('gt' => $now)));
						$queryFacet->setFilter($bool);
					}
					else
					{
						return;
					}
				}
				else
				{
					$queryFacet->setField($facet->getFieldName());
				}

				foreach ($ranges as $fromTo)
				{
					$queryFacet->addRange($fromTo[0] == '' ? null : $fromTo[0], $fromTo[1] == '' ? null : $fromTo[1]);
				}
				$event->setParam('facetQuery', $queryFacet);
				return;
			}
		}

		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_TERM)
		{
			$queryFacet = new \Elastica\Facet\Terms($facet->getFieldName());
			$queryFacet->setField($facet->getFieldName());
			$event->setParam('facetQuery', $queryFacet);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @param mixed $facetFilter
	 * @return \Elastica\Filter\AbstractFilter|null
	 */
	public function getFilterQuery($facet, $facetFilter)
	{
		$parameters = array('facet' => $facet, 'facetFilter' => $facetFilter);
		$event = new \Change\Events\Event('getFilterQuery', $this, $parameters);
		$this->getEventManager()->trigger($event);
		return $event->getParam('filterQuery');
	}
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFilterQuery(\Change\Events\Event $event)
	{
		/** @var $facet \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|\Rbs\Elasticsearch\Documents\Facet */
		$facet = $event->getParam('facet');
		$facetFilter = $event->getParam('facetFilter');
		$facetName = $facet->getFieldName();
		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_TERM)
		{
			$filterQuery = new \Elastica\Filter\Terms($facetName, is_array($facetFilter) ? $facetFilter : array($facetFilter));
			$event->setParam('filterQuery', $filterQuery);
		}
		elseif ($facet->getFacetType() === FacetDefinitionInterface::TYPE_RANGE)
		{
			if (!is_array($facetFilter)) {
				$facetFilter = [$facetFilter];
			}

			$ranges = [];
			foreach ($facetFilter as $data)
			{
				$fromTo = explode('::', $data);
				if (count($fromTo) === 2)
				{
					$args = array();
					if ($fromTo[0])
					{
						$args['from'] = $fromTo[0];
					}
					if ($fromTo[1])
					{
						$args['to'] = $fromTo[1];
					}
					$ranges[] = $args;
				}
			}

			if (count($ranges))
			{
				if ($facet instanceof \Rbs\Elasticsearch\Documents\Facet && $facet->getValuesExtractorName() == 'Price')
				{
					$cs = $event->getServices('commerceServices');
					if ($cs instanceof \Rbs\Commerce\CommerceServices && $cs->getContext()->getBillingArea())
					{
						$billingArea = $cs->getContext()->getBillingArea();
						$zone = $cs->getContext()->getZone();
						$now = (new \DateTime())->format(\DateTime::ISO8601);

						$filterQuery = new \Elastica\Filter\Nested();
						$filterQuery->setPath('prices');
						$nestedBool = new \Elastica\Query\Bool();
						$nestedBool->addMust(new \Elastica\Query\Term(['prices.billingAreaId' => $billingArea->getId()]));
						$nestedBool->addMust(new \Elastica\Query\Term(['prices.zone' => $zone ? $zone : '']));
						$nestedBool->addMust(new \Elastica\Query\Range('prices.startActivation', array('lte' => $now)));
						$nestedBool->addMust(new \Elastica\Query\Range('prices.endActivation', array('gt' => $now)));

						foreach ($ranges as $args)
						{
							$nestedBool->addShould(new \Elastica\Query\Range($zone ? 'prices.valueWithTax' : 'prices.value', $args));
						}

						$nestedBool->setMinimumNumberShouldMatch(1);
						$filterQuery->setQuery($nestedBool);
						$event->setParam('filterQuery', $filterQuery);
					}
				}
				else
				{
					$filterQuery = new \Elastica\Filter\Bool();
					foreach ($ranges as $args)
					{
						$filterQuery->addShould(new \Elastica\Filter\Range($facetName, $args));
					}
					$event->setParam('filterQuery', $filterQuery);
				}
			}
		}
	}
}
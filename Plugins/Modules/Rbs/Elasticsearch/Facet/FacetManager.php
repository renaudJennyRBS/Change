<?php
namespace Rbs\Elasticsearch\Facet;


/**
 * @name \Rbs\Elasticsearch\Facet\FacetManager
 */
class FacetManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait, \Change\Services\DefaultServicesTrait {
		\Change\Events\EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Elasticsearch_FacetManager';

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @var array
	 */
	protected $collections = array();

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		if ($this->sharedEventManager === null)
		{
			$this->sharedEventManager = $this->getApplication()->getSharedEventManager();
		}
		return $this->sharedEventManager;
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
		if ($this->applicationServices)
		{
			$config = $this->applicationServices->getApplication()->getConfiguration();
			return $config->getEntry('Rbs/Elasticsearch/Events/FacetManager', array());
		}
		return array();
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
		$eventManager->attach('getIndexerValue', array($this, 'onDefaultGetIndexerValue'), 5);
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
	 * @throws \RuntimeException
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		if ($this->collectionManager === null)
		{
			throw new \RuntimeException('CollectionManager not set.', 999999);
		}
		return $this->collectionManager;
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
		$event = new \Zend\EventManager\Event('getIndexerValue', $this, $parameters);
		$this->getEventManager()->trigger($event);
		$value = $event->getParam('value');
		return is_array($value) ? $value : null;

	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onDefaultGetIndexerValue(\Zend\EventManager\Event $event)
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
					$sku = $document->getSku();
					if ($sku)
					{
						$commerceServices = $indexDefinition->getCommerceServices();
						$price = $commerceServices->getPriceManager()->getPriceBySku($sku, $commerceServices->getWebStore());
						if ($price)
						{
							$value = array($facet->getFieldName() => $price->getValue(),
								$facet->getFieldName() .'_id' => $price->getId());
						}
					}
					break;
				case 'SkuThreshold':
					$sku = $document->getSku();
					$store = $indexDefinition->getCommerceServices()->getWebStore();
					if ($sku && $store)
					{
						$fieldName = $facet->getFieldName();
						$stockManager = $indexDefinition->getCommerceServices()->getStockManager();
						$level = $stockManager->getInventoryLevel($sku, $store);
						$threshold = $stockManager->getInventoryThreshold($sku, $store, $level);
						$thresholdTitle = $stockManager->getInventoryThresholdTitle($sku, $store, $threshold);
						$value = array($fieldName => $threshold,
							$fieldName . '_level' => $level,
							$fieldName . '_title' => $thresholdTitle);
					}
					break;
				case 'Attribute':
					$attribute = $facet->getAttributeIdInstance();
					if ($attribute)
					{
						$fieldName = $facet->getFieldName();
						$attributeValue = $attribute->getValue($document);
						if ($attributeValue instanceof \Change\Documents\AbstractDocument)
						{
							$value = array($fieldName => $attributeValue->getId());
						}
						elseif ($attributeValue instanceof \Change\Documents\DocumentArrayProperty)
						{
							$value = array($fieldName => $attributeValue->getIds());
						}
						elseif ($attributeValue instanceof \DateTime)
						{
							$value = array($fieldName => $attributeValue->format(\DateTime::ISO8601));
						}
						elseif (is_string($attributeValue) || is_bool($attributeValue) || is_numeric($attributeValue))
						{
							$value = array($fieldName => $attributeValue);
						}
					}
			}
		}

		if (is_array($value))
		{
			$event->setParam('value', $value);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetValue $facetValue
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface $facet
	 * @return \Rbs\Elasticsearch\Facet\FacetValue
	 */
	public function updateFacetValueTitle($facetValue, $facet)
	{
		if ($facet instanceof \Rbs\Elasticsearch\Documents\Facet)
		{
			$collection = $this->getCollectionByCode($facet->getCollectionCode());
			if ($collection)
			{
				$value = is_string($facetValue->getValue()) ? \Change\Stdlib\String::toLower($facetValue->getValue()) : $facetValue->getValue();
				foreach ($collection->getItems() as $item)
				{
					$iv = \Change\Stdlib\String::toLower($item->getValue());
					if ($iv == $value)
					{
						$facetValue->setValueTitle($item->getTitle());
					}
				}
			}
			elseif (($attribute = $facet->getAttributeIdInstance()) !== null)
			{
				$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
				$attributeEngine->setCollectionManager($this->getCollectionManager());
				$attrDef = $attributeEngine->buildAttributeDefinition($attribute);
				$attrType = $attrDef['type'];
				if ($attrType == \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENT || $attrType == \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTARRAY)
				{
					$document = $attribute->getDocumentServices()->getDocumentManager()->getDocumentInstance($facetValue->getValue());
					if ($document)
					{
						$facetValue->setValueTitle($document->getDocumentModel()->getPropertyValue($document, 'title'));
					}
				}
			}
		}
		elseif ($facet instanceof ModelFacetDefinition)
		{
			$model = $this->getDocumentServices()->getModelManager()->getModelByName($facetValue->getValue());
			if ($model)
			{
				$facetValue->setValueTitle($this->getApplicationServices()->getI18nManager()->trans($model->getLabelKey()));
			}
		}
		return $facetValue;
	}
}
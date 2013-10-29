<?php
namespace Rbs\Elasticsearch\Index;

use Change\Documents\Interfaces\Publishable;
use Change\Documents\Query\Query;
use Elastica\Document;

/**
 * @name \Rbs\Elasticsearch\Index\StoreIndexer
 */
class StoreIndexer extends FullTextIndexer
{
	/**
	 * @var string[]
	 */
	protected $indexableModelNames = null;

	/**
	 * @return string[]
	 */
	protected function getIndexableModelNames()
	{
		if ($this->indexableModelNames === null)
		{
			$this->indexableModelNames = $indexableModelNames = array();

			$modelManager = $this->getDocumentServices()->getModelManager();
			$model = $modelManager->getModelByName('Rbs_Catalog_Product');
			if ($model)
			{
				$indexableModelNames = array_merge(array($model->getName()), $model->getDescendantsNames(), $indexableModelNames);
			}
			else
			{
				return $this->indexableModelNames;
			}
			$this->indexableModelNames = $indexableModelNames;
		}
		return $this->indexableModelNames;
	}

	/**
	 * @param Event $event
	 */
	public function onIndexDocument($event)
	{
		$this->setEventContext($event);
		$LCID = $event->getParam('LCID');

		/* @var $model  \Change\Documents\AbstractModel */
		$model = $event->getParam('model');
		if ($model && in_array($model->getName(), $this->getIndexableModelNames()))
		{
			$document = $event->getParam('document');
			if (!($document instanceof \Change\Documents\AbstractDocument))
			{
				$this->addDeleteDocumentId($event->getParam('id'));
				return;
			}

			if ($document instanceof \Rbs\Catalog\Documents\Product)
			{
				$publicationStatus = $model->getPropertyValue($document, 'publicationStatus', Publishable::STATUS_FILED);
				if ($publicationStatus == Publishable::STATUS_PUBLISHABLE)
				{
					$this->addProduct($document, $LCID);
					return;
				}
				elseif (in_array($publicationStatus, array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_FROZEN,
					Publishable::STATUS_FILED))
				)
				{
					$this->addDeleteDocumentId($event->getParam('id'), $LCID);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param string $LCID
	 */
	protected function addProduct($product, $LCID)
	{
		$websiteIds = array();
		foreach ($product->getPublicationSections() as $section)
		{
			$website = $section->getWebsite();
			if (!in_array($website->getId(), $websiteIds))
			{
				$websiteIds[] = $website->getId();
				$indexManager = $this->getIndexManager();
				foreach ($this->getIndexesDefinition() as $storeIndex)
				{
					if ($storeIndex->getAnalysisLCID() === $LCID && $storeIndex->getWebsiteId() == $website->getId())
					{
						if (!$storeIndex->getCommerceServices())
						{
							$commerceServices = new \Rbs\Commerce\Services\CommerceServices($indexManager->getApplicationServices(), $indexManager->getDocumentServices());
							$storeIndex->setCommerceServices($commerceServices);
						}

						$elasticaDocument = new Document($product->getId(), array(), 'product', $storeIndex->getName());
						$this->populatePublishableDocument($product, $elasticaDocument, $storeIndex);

						$canonicalSection = $product->getCanonicalSection($website);
						if ($canonicalSection)
						{
							$elasticaDocument->set('canonicalSectionId', $canonicalSection->getId());
						}
						$this->getIndexManager()->documentToAdd($storeIndex->getClientName(), $elasticaDocument);
					}
				}
			}
		}
	}

	/**
	 * @return \Change\Documents\DocumentCollection|\Rbs\Elasticsearch\Documents\StoreIndex[]
	 */
	protected function getIndexesDefinition()
	{
		if ($this->indexesDefinition === null)
		{
			$query = new Query($this->getDocumentServices(), 'Rbs_Elasticsearch_StoreIndex');
			$query->andPredicates($query->activated());
			$this->indexesDefinition = $query->getDocuments();
		}
		return $this->indexesDefinition;
	}

	/**
	 * @param Event $event
	 */
	public function onPopulateDocument(Event $event)
	{
		parent::onPopulateDocument($event);
		$storeIndex = $event->getParam('indexDefinition');
		$indexManager = $event->getIndexManager();
		if (($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex) && $indexManager)
		{
			$elasticaDocument = $event->getParam('elasticaDocument');
			$document = $event->getParam('document');
			if ($elasticaDocument instanceof Document && $document instanceof \Change\Documents\AbstractDocument)
			{
				$values = $indexManager->getFacetManager()->getIndexerValues($storeIndex, $document);
				foreach ($values as $fieldName => $value)
				{
					$elasticaDocument->set($fieldName, $value);
				}
			}
		}
	}

	/**
	 * @param string $mappingName
	 * @param string $analysisLCID
	 * @param integer $websiteId
	 * @param integer $storeId
	 * @return \Rbs\Elasticsearch\Documents\StoreIndex|null
	 */
	protected function getIndexDefinitionByMapping($mappingName, $analysisLCID, $websiteId, $storeId)
	{
		/* @var $storeIndex \Rbs\Elasticsearch\Documents\StoreIndex */
		foreach ($this->getIndexesDefinition() as $storeIndex)
		{
			if ($storeIndex->getMappingName() === $mappingName
				&& $storeIndex->getAnalysisLCID() === $analysisLCID
				&& $storeIndex->getWebsiteId() == $websiteId
				&& $storeIndex->getStoreId() == $storeId
			)
			{
				return $storeIndex;
			}
		}
		return null;
	}

	/**
	 * @param Event $event
	 */
	public function onFindIndexDefinition(Event $event)
	{
		$this->setEventContext($event);

		$indexName = $event->getParam('indexName');
		$clientName = $event->getParam('clientName');
		if ($indexName && $clientName)
		{
			$indexDefinition = $this->getIndexDefinitionByName($clientName, $indexName);
			if ($indexDefinition)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
			return;
		}

		$website = $event->getParam('website');
		if ($website instanceof \Rbs\Website\Documents\Website)
		{
			$websiteId = $website->getId();
		}
		elseif (is_numeric($website))
		{
			$websiteId = intval($website);
		}
		else
		{
			$websiteId = null;
		}

		$store = $event->getParam('store');
		if ($store instanceof \Rbs\Store\Documents\WebStore)
		{
			$storeId = $store->getId();
		}
		elseif (is_numeric($store))
		{
			$storeId = intval($store);
		}
		else
		{
			$storeId = null;
		}

		$mappingName = $event->getParam('mappingName');
		$analysisLCID = $event->getParam('analysisLCID');
		if ($mappingName === 'store' && $analysisLCID && $websiteId && $storeId)
		{
			$indexDefinition = $this->getIndexDefinitionByMapping($mappingName, $analysisLCID, $websiteId, $storeId);
			if ($indexDefinition)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
			return;
		}
	}
}
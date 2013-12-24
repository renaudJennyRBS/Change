<?php
namespace Rbs\Elasticsearch\Index;

use Change\Documents\Interfaces\Publishable;
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
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices)
	{
		$this->commerceServices = $commerceServices;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\CommerceServices
	 */
	protected function getCommerceServices()
	{
		return $this->commerceServices;
	}

	protected function setEventContext(Event $event)
	{
		parent::setEventContext($event);
		$this->commerceServices = $event->getServices('commerceServices');
	}

	/**
	 * @param Event $event
	 */
	public function onIndexDocument($event)
	{
		$this->setEventContext($event);
		/* @var $model \Change\Documents\AbstractModel */
		$model = $event->getParam('model');
		if (!$model)
		{
			return;
		}
		$documentId = $event->getParam('id');
		$LCID = $event->getParam('LCID');

		if ($model->isInstanceOf('Rbs_Catalog_Product'))
		{
			$document = $event->getParam('document');
			if (!($document instanceof \Change\Documents\AbstractDocument))
			{
				$this->addDeleteDocumentId($documentId);
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
					$this->addDeleteDocumentId($documentId, $LCID);
				}
			}
		}
		elseif ($model->isInstanceOf('Rbs_Stock_InventoryEntry'))
		{
			$inventoryEntry = $event->getParam('document');
			if ($inventoryEntry instanceof \Rbs\Stock\Documents\InventoryEntry && $inventoryEntry->getSkuId())
			{
				$skuId = $inventoryEntry->getSkuId();
				$this->indexProductsBySkuId($skuId);
			}
		}
		elseif ($model->isInstanceOf('Rbs_Price_Price'))
		{
			$price = $event->getParam('document');
			if ($price instanceof \Rbs\Price\Documents\Price && $price->getSkuId())
			{
				$skuId = $price->getSkuId();
				$this->indexProductsBySkuId($skuId);
			}
		}
		elseif ($model->isInstanceOf('Rbs_Catalog_ProductListItem'))
		{
			$document = $event->getParam('document');
			$indexedItem = $this->getIndexedListItemInfo($documentId);
			if ($document instanceof \Rbs\Catalog\Documents\ProductListItem)
			{
				if ($indexedItem)
				{
					list($itemId, $listId, $productId, $position) = $indexedItem;
					if ($document->getPosition() != $position)
					{
						$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
						$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
						$subQuery->andPredicates($subQuery->eq('productList', $listId));
						$products = $query->getDocuments();
						$this->indexProducts($products);
					}
					elseif ($document->getProduct())
					{
						$this->indexProducts([$document->getProduct()]);
					}
				}
				elseif ($document->getProduct())
				{
					$this->indexProducts([$document->getProduct()]);
				}
			}
			else
			{
				if ($indexedItem)
				{
					list($itemId, $listId, $productId, $position) = $indexedItem;
					$product = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($productId);
					if ($product instanceof \Rbs\Catalog\Documents\Product)
					{
						$this->indexProducts([$product]);
					}

					if ($position < 0)
					{
						$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
						$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
						$subQuery->andPredicates($subQuery->eq('productList', $listId));
						$products = $query->getDocuments();
						$this->indexProducts($products);
					}
				}
			}
		}
	}

	/**
	 * @param integer $itemId
	 * @return array|null
	 */
	protected function getIndexedListItemInfo($itemId)
	{
		$indexManager = $this->getIndexManager();
		foreach ($this->getIndexesDefinition() as $storeIndex)
		{
			$client = $indexManager->getClient($storeIndex->getClientName());
			if (!$client){continue;}
			$index = $client->getIndex($storeIndex->getName());
			if ($index->exists())
			{
				$nested = new \Elastica\Query\Nested();
				$nested->setPath('listItems');
				$nestedBool = new \Elastica\Query\Bool();
				$nestedBool->addMust(new \Elastica\Query\Term(['itemId' => $itemId]));
				$nested->setQuery($nestedBool);
				$query = new \Elastica\Query($nested);
				$query->setSize(1);
				$query->setFields(['listItems']);

				$results = $index->getType($storeIndex->getDefaultTypeName())->search($query)->getResults();
				if (count($results))
				{
					/** @var $result \Elastica\Result */
					$result = $results[0];
					$productId = intval($result->getId());
					foreach ($result->getFields()['listItems'] as $listItem)
					{
						if ($listItem['itemId'] == $itemId)
						{
							$listId = $listItem['listId'];
							$position = $listItem['position'];
							$found = [$itemId, $listId, $productId, $position];
							return $found;
						}
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param $skuId
	 */
	protected function indexProductsBySkuId($skuId)
	{
		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
		$query->andPredicates($query->eq('sku', $skuId));
		$this->indexProducts($query->getDocuments());
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product[]|\Change\Documents\DocumentCollection $products
	 */
	protected function indexProducts($products)
	{
		$toIndex = array();

		/** @var $product \Rbs\Catalog\Documents\Product */
		foreach ($products as $product)
		{
			foreach ($product->getLCIDArray() as $LCID)
			{
				$toIndex[] = array('LCID' => $LCID, 'id' => $product->getId(),
					'model' => $product->getDocumentModelName(), 'deleted' => false);
			}
		}
		if (count($toIndex))
		{
			$this->getIndexManager()->dispatchIndexationEvents($toIndex);
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
				foreach ($this->getIndexesDefinition() as $storeIndex)
				{
					if ($storeIndex->getAnalysisLCID() === $LCID && $storeIndex->getWebsiteId() == $website->getId())
					{
						if (!$storeIndex->getCommerceServices())
						{
							$storeIndex->setCommerceServices($this->getCommerceServices());
						}
						$elasticaDocument = new Document($product->getId(), array(), $storeIndex->getDefaultTypeName(), $storeIndex->getName());
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
			$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_StoreIndex');
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

				if ($document instanceof \Rbs\Catalog\Documents\Product)
				{
					$q = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
					$q->andPredicates($q->activated(), $q->eq('product', $document));
					$qb = $q->dbQueryBuilder();
					$fb = $qb->getFragmentBuilder();
					$qb->addColumn($fb->alias($q->getColumn('id'), 'itemId'));
					$qb->addColumn($fb->alias($q->getColumn('productList'), 'listId'));
					$qb->addColumn($fb->alias($q->getColumn('position'), 'position'));
					$select = $qb->query();
					$listItems = $select->getResults($select->getRowsConverter()->addIntCol('itemId', 'listId', 'position'));
					$elasticaDocument->set('listItems', $listItems);
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
	protected function getIndexDefinitionByMapping($mappingName, $analysisLCID, $websiteId, $storeId = null)
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
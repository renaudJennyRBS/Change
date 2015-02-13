<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping;

/**
* @name \Rbs\Storeshipping\ProductLocatorManager
*/
class ProductLocatorManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ProductLocatorManager';

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Storeshipping\StoreLocatorManager
	 */
	protected $storeLocatorManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Rbs\Storeshipping\Db\StockQueries
	 */
	protected $stockQueries;

	/**
	 * @return string
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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/ProductLocatorManager');
	}

	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('countStoresWithStockForSku', [$this, 'onDefaultCountStoresWithStockForSku'], 5);
		$eventManager->attach('getStoresDataWithStockForSku', [$this, 'onDefaultGetStoresDataWithStockForSku'], 5);
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

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
	 * @return StoreLocatorManager
	 */
	protected function getStoreLocatorManager()
	{
		return $this->storeLocatorManager;
	}

	/**
	 * @param StoreLocatorManager $storeLocatorManager
	 * @return $this
	 */
	public function setStoreLocatorManager($storeLocatorManager)
	{
		$this->storeLocatorManager = $storeLocatorManager;
		return $this;
	}

	/**
	 * @return \Rbs\Stock\StockManager
	 */
	protected function getStockManager()
	{
		return $this->stockManager;
	}

	/**
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @return $this
	 */
	public function setStockManager($stockManager)
	{
		$this->stockManager = $stockManager;
		return $this;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|string|integer $sku
	 * @return integer
	 */
	protected function resolveSkuId($sku)
	{
		if (is_string($sku))
		{
			$sku = $this->getStockManager()->getSkuByCode($sku);
		}
		if ($sku instanceof \Rbs\Stock\Documents\Sku)
		{
			return $sku->getId();
		}
		elseif (is_int($sku))
		{
			return $sku;
		} else {
			return 0;
		}
	}

	/**
	 * @param \Rbs\Storelocator\Documents\Store|string|integer $store
	 * @return integer
	 */
	protected function resolveStoreId($store)
	{
		if (is_string($store))
		{
			$store = $this->getStoreLocatorManager()->getStoreByCode($store);
		}
		if ($store instanceof \Rbs\Storelocator\Documents\Store)
		{
			return $store->getId();
		}
		elseif (is_int($store))
		{
			return $store;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Storeshipping\Db\StockQueries
	 */
	protected function initStockQueries(\Change\Events\Event $event)
	{
		$this->stockQueries = new \Rbs\Storeshipping\Db\StockQueries($event->getApplicationServices()->getDbProvider());
		return $this->stockQueries;
	}


	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|string|integer $sku
	 * @param integer $minStockLevel
	 * @return integer|null
	 */
	public function countStoresWithStockForSku($sku, $minStockLevel = 1)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['sku' => $sku, 'minStockLevel' => $minStockLevel]);
		$eventManager->trigger('countStoresWithStockForSku', $this, $args);
		if (isset($args['count']) && is_numeric($args['count']))
		{
			return $args['count'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCountStoresWithStockForSku(\Change\Events\Event $event)
	{
		$count = 0;
		$sku = $event->getParam('sku');
		$minStockLevel = intval($event->getParam('minStockLevel'));
		$skuId = $this->resolveSkuId($sku);
		if ($skuId)
		{
			 if ($this->stockQueries === null)
			 {
				 $this->initStockQueries($event);
			 }
			$count = $this->stockQueries->countStoreForSkuId($skuId, $minStockLevel);
		}
		$event->setParam('count', $count);
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|string|integer $sku
	 * @param integer $minStockLevel
	 * @param array $context ['data' => ['allowedStores' => array], 'website' => document]
	 * @return array ['pagination' => array, 'items' => array]
	 */
	public function getStoresDataWithStockForSku($sku, $minStockLevel = 1, array $context)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['sku' => $sku, 'minStockLevel' => $minStockLevel, 'context' => $context]);
		$eventManager->trigger('getStoresDataWithStockForSku', $this, $args);

		$storesData = [];
		$pagination = ['offset' => 0, 'limit' => 100, 'count' => 0];
		if (isset($args['storesData']) && is_array($args['storesData']))
		{
			$storesData = $args['storesData'];
			if (isset($args['pagination']) && is_array($args['pagination']))
			{
				$pagination = $args['pagination'];
			}
		}
		return ['pagination' => $pagination, 'items' => $storesData];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetStoresDataWithStockForSku(\Change\Events\Event $event)
	{
		if (is_array($event->getParam('storesData')))
		{
			return;
		}

		$sku = $event->getParam('sku');
		$minStockLevel = intval($event->getParam('minStockLevel'));
		$context = $event->getParam('context');
		$contextData = (isset($context['data']) && is_array($context['data']) ? $context['data'] : []) + ['allowedStores' => []];
		$storesData = [];

		$skuId = $this->resolveSkuId($sku);
		if ($skuId)
		{
			if ($this->stockQueries === null)
			{
				$this->initStockQueries($event);
			}

			$allowedStoreIds = array_map(function($storeData) {
				if ($storeData instanceof \Change\Documents\AbstractDocument) {
					return $storeData->getId();
				} elseif (is_array($storeData) && isset($storeData['common']['id'])) {
					return intval($storeData['common']['id']);
				} elseif (is_numeric($storeData)) {
					return intval($storeData);
				}
				return 0;
			}, $contextData['allowedStores']);

			$storeIds = $this->stockQueries->storeIdsForSkuId($skuId, $minStockLevel, $allowedStoreIds);
			$storeLocatorManager = $this->getStoreLocatorManager();
			$context['detailed'] = false;

			foreach ($storeIds as $storeId)
			{
				$storeData = $storeLocatorManager->getStoreData($storeId, $context);
				if ($storeData)
				{
					$storesData[] = $storeData;
				}
			}
			$event->setParam('pagination', ['offset' => 0, 'limit' => max(10, count($storesData)), 'count' => count($storesData)]);
			$event->setParam('storesData', $storesData);
		}
	}
}
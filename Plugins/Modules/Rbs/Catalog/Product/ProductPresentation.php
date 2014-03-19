<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductPresentation
 */
class ProductPresentation
{
	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var integer
	 */
	protected $productId;

	/**
	 * @var \Rbs\Store\Documents\WebStore
	 */
	protected $webStore;

	/**
	 * @var \Rbs\Price\Tax\BillingAreaInterface
	 */
	protected $billingArea;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var array
	 */
	protected $prices = null;

	/**
	 * @var array
	 */
	protected $stock = null;

	/**
	 * @var array
	 */
	protected $visuals = null;

	/**
	 * @var array
	 */
	protected $general = array();

	/**
	 * @var array
	 */
	protected $pictograms = null;

	/**
	 * @var array
	 */
	protected $attributesConfiguration = null;

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
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
	}

	/**
	 * @return \Change\Http\Web\UrlManager
	 */
	protected function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @return $this
	 */
	public function setBillingArea($billingArea)
	{
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\BillingAreaInterface
	 */
	public function getBillingArea()
	{
		return $this->billingArea;
	}

	/**
	 * @param int $productId
	 * @return $this
	 */
	public function setProductId($productId)
	{
		$this->productId = $productId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @return $this
	 */
	public function setWebStore($webStore)
	{
		$this->webStore = $webStore;
		return $this;
	}

	/**
	 * @return \Rbs\Store\Documents\WebStore
	 */
	public function getWebStore()
	{
		return $this->webStore;
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		return $this->zone;
	}

	/**
	 * @param array $general
	 * @return $this
	 */
	public function setGeneral(array $general)
	{
		$this->general = $general;
		return $this;
	}

	/**
	 * @param array $general
	 * @return $this
	 */
	public function addGeneral(array $general)
	{
		$this->general = array_merge($this->general, $general);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getGeneral()
	{
		return $this->general;
	}

	/**
	 * @param array $stock
	 * @return $this
	 */
	public function setStock($stock)
	{
		$this->stock = $stock;
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function resetStock()
	{
		$this->stock = null;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getStock()
	{
		if ($this->stock === null)
		{
			$this->stock = $this->catalogManager->getStockInfo($this->productId, $this->webStore);
		}
		return $this->stock;
	}

	/**
	 * @return $this
	 */
	protected function resetPrice()
	{
		$this->prices = array();
		return $this;
	}

	/**
	 * @param int $quantity
	 * @return array
	 */
	public function getPrices($quantity = 1)
	{
		if (!isset($this->prices[$quantity]))
		{
			$this->prices[$quantity] = $this->catalogManager->getPricesInfos($this->productId, $quantity, $this->webStore,
				$this->billingArea, $this->zone);
		}
		return $this->prices[$quantity];
	}

	/**
	 * @param array $formats
	 * @return array
	 */
	public function getPictograms($formats = array('pictogram' => ['maxWidth' => 60, 'maxHeight' => 45]))
	{
		return $this->catalogManager->getPictogramsInfos($this->productId, $formats);
	}

	/**
	 * @param array $formats
	 * @return array
	 */
	public function getFirstVisual($formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
		'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]))
	{
		return $this->catalogManager->getVisualsInfos($this->productId, $formats, true);
	}

	/**
	 * @param array $formats
	 * @return array
	 */
	public function getVisuals($formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
		'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]))
	{
		return $this->catalogManager->getVisualsInfos($this->productId, $formats, false);
	}

	/**
	 * @return array
	 */
	public function getAttributesConfiguration()
	{
		if ($this->attributesConfiguration === null)
		{
			$this->attributesConfiguration = $this->catalogManager->getAttributesConfiguration($this->productId);
		}
		return $this->attributesConfiguration;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function evaluate($quantity = 1)
	{
		$this->resetPrice();
		$this->resetStock();
		if ($quantity && $this->webStore)
		{
			$prices = $this->getPrices($quantity);
			$stock = $this->getStock();

			if ($this->general['hasOwnSku'] !== true || !isset($prices['price']))
			{
				$this->general['canBeOrdered'] = false;
			}
			elseif (isset($this->general['allowBackorders']) && $this->general['allowBackorders'] === true)
			{
				$this->general['canBeOrdered'] = true;
			}
			elseif (isset($stock['level']) && $stock['level'] > 0 && $stock['level'] >= $stock['minQuantity'])
			{
				$this->general['canBeOrdered'] = true;
			}
			else
			{
				$this->general['canBeOrdered'] = false;
			}
		}
		return $this;
	}

	/**
	 * @param array $formats
	 * @return array
	 */
	public function toArray($formats)
	{
		$array = [];
		$array['productId'] = $this->productId;
		$array['key'] = $array['productId'];
		$array['general'] = $this->getGeneral();
		$array['stock'] = $this->getStock();
		$array['prices'] = $this->getPrices();
		if (isset($formats['visuals']))
		{
			$array['visuals'] = $this->getVisuals($formats['visuals']);
		}
		else
		{
			$array['visuals'] = $this->getVisuals();
		}
		if (isset($formats['pictograms']))
		{
			$array['pictograms'] = $this->getPictograms($formats['pictograms']);
		}
		else
		{
			$array['pictograms'] = $this->getPictograms();
		}
		$array['templateSuffix'] = $this->getTemplateSuffix();
		return $array;
	}

	/**
	 * @return string
	 */
	public function getTemplateSuffix()
	{
		return 'simple';
	}
}
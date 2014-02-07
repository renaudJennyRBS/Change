<?php
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductPresentation
 */
class ProductPresentation
{
	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Rbs\Catalog\Documents\Product
	 */
	protected $product;

	/**
	 * @var integer
	 */
	protected $webStoreId;

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
	protected $general = null;

	/**
	 * @var array
	 */
	protected $pictograms = null;

	/**
	 * @var array
	 */
	protected $attributesConfiguration = null;

	/**
	 * @var array
	 */
	protected $variantsConfiguration = null;

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $webStoreId
	 * @param \Change\Http\Web\UrlManager $urlManager
	 */
	public function __construct(\Rbs\Commerce\CommerceServices $commerceServices, \Rbs\Catalog\Documents\Product $product,
		$webStoreId, $urlManager)
	{
		$this->commerceServices = $commerceServices;
		$this->product = $product;
		$this->webStoreId = $webStoreId;
		$this->urlManager = $urlManager;
	}

	/**
	 * @param integer $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId)
	{
		$this->resetPrice();
		$this->resetStock();
		$this->webStoreId = $webStoreId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getWebStoreId()
	{
		return $this->webStoreId;
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
	 * @return array
	 */
	public function getStock()
	{
		if ($this->stock === null)
		{
			$this->stock = $this->commerceServices->getCatalogManager()->getStockInfo($this->product, $this->webStoreId);
		}
		return $this->stock;
	}

	/**
	 * @param int $quantity
	 * @return array
	 */
	public function getPrices($quantity = 1)
	{
		if ($this->prices === null)
		{
			$billingArea = $this->commerceServices->getContext()->getBillingArea();
			$zone = $this->commerceServices->getContext()->getZone();
			$this->prices = $this->commerceServices->getCatalogManager()->getPricesInfos($this->product, $quantity, $billingArea, $zone, $this->webStoreId);
		}
		return $this->prices;
	}

	/**
	 * @return array
	 */
	public function getGeneral()
	{
		if ($this->general === null)
		{
			$this->general = $this->commerceServices->getCatalogManager()->getGeneralInfo($this->product, $this->urlManager);
		}
		return $this->general;
	}

	/**
	 * @return array
	 */
	public function getPictograms()
	{
		if ($this->pictograms === null)
		{
			// TODO
			$this->pictograms = null;
		}
		return $this->pictograms;
	}

	/**
	 * @return array
	 */
	public function getVisuals()
	{
		if ($this->visuals === null)
		{
			$this->visuals = $this->commerceServices->getCatalogManager()->getVisualsInfos($this->product);
		}
		return $this->visuals;
	}

	/**
	 * @return array
	 */
	public function getVariantsConfiguration()
	{
		if ($this->variantsConfiguration === null)
		{
			$this->variantsConfiguration = $this->commerceServices->getCatalogManager()->getVariantsConfiguration($this->product);
		}
		return $this->variantsConfiguration;
	}

	/**
	 * @return \Rbs\Catalog\Documents\Product
	 */
	public function getProduct()
	{
		return $this->product;
	}

	/**
	 * @return array
	 */
	public function getAttributesConfiguration()
	{
		if ($this->attributesConfiguration === null)
		{
			$this->attributesConfiguration = $this->commerceServices->getCatalogManager()->getAttributesConfiguration($this->product);
		}
		return $this->attributesConfiguration;
	}

	protected function resetPrice()
	{
		/*$this->prices['currencyCode'] = null;
		$this->prices['price'] = null;
		$this->prices['formattedPrice'] = null;
		$this->prices['priceWithTax'] = null;
		$this->prices['formattedPriceWithTax'] = null;
		$this->prices['priceWithoutDiscount'] = null;
		$this->prices['formattedPriceWithoutDiscount'] = null;
		$this->prices['priceWithoutDiscountWithTax'] = null;
		$this->prices['formattedPriceWithoutDiscountWithTax'] = null;
		$this->prices['ecoTax'] = null;
		$this->prices['formattedEcoTax'] = null;*/
		$this->prices = null;
	}

	protected function resetStock()
	{
		/*$this->stock['sku'] = null;
		$this->stock['level'] = null;
		$this->stock['threshold'] = null;
		$this->stock['thresholdClass'] = null;
		$this->stock['thresholdTitle'] = null;
		$this->stock['minQuantity'] = null;
		$this->stock['maxQuantity'] = null;
		$this->stock['quantityIncrement'] = null;*/
		$this->stock = null;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function evaluate($quantity = 1)
	{
		$this->resetPrice();
		$this->resetStock();

		if ($quantity && $this->webStoreId)
		{
			$this->getPrices($quantity);
			$this->getStock();
		}
		return $this;
	}

	public function toArray()
	{
		$array = [];
		$array['productId'] = $this->product->getId();
		$array['key'] = $array['productId'];
		$array['designation'] = $this->getGeneral()['title'];

		/*if (!is_array($this->stock))
	{
		$this->resetStock();
	}
		$array['stock'] = $this->stock;

		if (!is_array($this->prices))
		{
			$this->resetPrice();
		}
		$array['prices'] = $this->prices;*/

		$array['stock'] = $this->getStock();
		$array['prices'] = $this->getPrices();
		return $array;
	}
}
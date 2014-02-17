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

		$this->general = $this->getGeneral();
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

	public function getFirstVisual($formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
		'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]))
	{
		$v = $this->doGetVisuals($formats, true);
		return $v;
	}

	public function getVisuals($formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
		'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]))
	{
		// TODO Cache with formats
		/*if ($this->visuals === null)
		{*/
		$this->visuals = $this->doGetVisuals($formats, false);
		/*}*/
		return $this->visuals;
	}

	/**
	 * @param array $formats
	 * @param boolean $onlyFirst
	 * @return array
	 */
	protected function doGetVisuals($formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
		'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]), $onlyFirst)
	{
		return $this->commerceServices->getCatalogManager()->getVisualsInfos($this->product, $formats, $onlyFirst);
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
		$this->prices = null;
	}

	protected function resetStock()
	{
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

			if ($this->general['hasOwnSku'] === true && isset($this->prices['price']) &&
				((isset($this->stock['level']) && $this->stock['level'] > 0 && $this->stock['level'] >= $this->stock['minQuantity']) || (isset($this->general['allowBackorders']) && $this->general['allowBackorders'] === true))
				)
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
		$array['productId'] = $this->product->getId();
		$array['key'] = $array['productId'];
		$array['general'] = $this->getGeneral();
		$array['stock'] = $this->getStock();
		$array['prices'] = $this->getPrices();
		if ($formats !== null)
		{
			$array['visuals'] = $this->getVisuals($formats);
		}
		else
		{
			$array['visuals'] = $this->getVisuals();
		}
		return $array;
	}
}
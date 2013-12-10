<?php
namespace Rbs\Catalog\Product;

use Rbs\Price\Tax\TaxApplication;

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
	protected $prices = array();

	/**
	 * @var array
	 */
	protected $stock = array();

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $webStoreId
	 */
	public function __construct(\Rbs\Commerce\CommerceServices $commerceServices, \Rbs\Catalog\Documents\Product $product, $webStoreId)
	{
		$this->commerceServices = $commerceServices;
		$this->product = $product;
		$this->webStoreId = $webStoreId;
	}

	/**
	 * @param integer $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId)
	{
		$this->resetPrice();
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
		return $this->stock;
	}

	/**
	 * @return array
	 */
	public function getPrices()
	{
		return $this->prices;
	}

	protected function resetPrice()
	{
		$this->prices['currencyCode'] = null;
		$this->prices['price'] = null;
		$this->prices['formattedPrice'] = null;
		$this->prices['priceWithTax'] = null;
		$this->prices['formattedPriceWithTax'] = null;
		$this->prices['priceWithoutDiscount'] = null;
		$this->prices['formattedPriceWithoutDiscount'] = null;
		$this->prices['priceWithoutDiscountWithTax'] = null;
		$this->prices['formattedPriceWithoutDiscountWithTax'] = null;
		$this->prices['ecoTax'] = null;
		$this->prices['formattedEcoTax'] = null;
	}

	protected function resetStock()
	{
		$this->stock['sku'] = null;
		$this->stock['level'] = null;
		$this->stock['threshold'] = null;
		$this->stock['thresholdClass'] = null;
		$this->stock['thresholdTitle'] = null;
		$this->stock['minQuantity'] = null;
		$this->stock['maxQuantity'] = null;
		$this->stock['quantityIncrement'] = null;
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
			$sku = $this->product->getSku();
			if ($sku)
			{
				$stm = $this->commerceServices->getStockManager();
				$level = $stm->getInventoryLevel($sku, $this->webStoreId);
				$threshold = $stm->getInventoryThreshold($sku, $this->webStoreId, $level);
				$this->stock['sku'] = $sku->getCode();
				$this->stock['level'] = $level;
				$this->stock['threshold'] = $threshold;
				$this->stock['thresholdClass'] = 'stock-' . \Change\Stdlib\String::toLower($threshold);
				switch ($threshold)
				{
					case \Rbs\Stock\Services\StockManager::THRESHOLD_AVAILABLE:
						$this->stock['thresholdClass'] .= ' alert-success';
						break;
					case \Rbs\Stock\Services\StockManager::THRESHOLD_UNAVAILABLE:
						$this->stock['thresholdClass'] .= 'alert-danger';
						break;
				}
				$this->stock['thresholdTitle'] = $stm->getInventoryThresholdTitle($sku, $this->webStoreId, $threshold);
				$this->stock['minQuantity'] = $sku->getMinQuantity();
				$this->stock['maxQuantity'] = $sku->getMaxQuantity()  ? min(max($sku->getMinQuantity(), $sku->getMaxQuantity()), $level) : $level;
				$this->stock['quantityIncrement'] = $sku->getQuantityIncrement() ? $sku->getQuantityIncrement() : 1;

				$billingArea = $this->commerceServices->getContext()->getBillingArea();
				if ($billingArea)
				{
					$priceManager = $this->commerceServices->getPriceManager();
					$price = $priceManager->getPriceBySku($sku, ['webStore' => $this->webStoreId, 'billingArea' => $billingArea]);
					if ($price && ($priceValue = $price->getValue()) !== null)
					{
						$this->prices['currencyCode'] = $currencyCode = $billingArea->getCurrencyCode();
						$taxManager = $this->commerceServices->getTaxManager();
						$this->prices['price'] = ($priceValue * $quantity);
						$this->prices['formattedPrice'] = $priceManager->formatValue($this->prices['price'], $currencyCode);

						$taxApplication = $taxManager->getTaxByValue($priceValue, $price->getTaxCategories());
						if (count($taxApplication))
						{
							$tax = array_reduce($taxApplication, function ($result, TaxApplication $cartTax) use ($quantity)
							{
								return $result + $cartTax->getValue() * $quantity;
							}, 0.0);
							$this->prices['priceWithTax'] = ($priceValue * $quantity) + $tax;
							$this->prices['formattedPriceWithTax'] = $priceManager->formatValue($this->prices['priceWithTax'], $currencyCode);
						}

						$oldValue = $price->getBasePriceValue();
						if ($oldValue !== null)
						{
							$this->prices['priceWithoutDiscount'] = ($oldValue * $quantity);
							$this->prices['formattedPriceWithoutDiscount'] = $priceManager->formatValue($this->prices['priceWithoutDiscount'], $currencyCode);
							$taxApplication = $taxManager->getTaxByValue($oldValue, $price->getTaxCategories());
							if (count($taxApplication))
							{
								$tax = array_reduce($taxApplication, function ($result, TaxApplication $cartTax) use ($quantity)
								{
									return $result + $cartTax->getValue() * $quantity;
								}, 0.0);
								$this->prices['priceWithoutDiscountWithTax'] = ($oldValue * $quantity) + $tax;
								$this->prices['formattedPriceWithoutDiscountWithTax'] = $priceManager->formatValue($this->prices['priceWithoutDiscountWithTax'], $currencyCode);
							}
						}

						if ($price instanceof \Rbs\Price\Documents\Price)
						{
							if ($price->getEcoTax() !== null)
							{
								$this->prices['ecoTax'] = ($price->getEcoTax() * $quantity);
								$this->prices['formattedEcoTax'] = $priceManager->formatValue($this->prices['ecoTax'], $currencyCode);
							}
						}
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->prices['price'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		return $this->prices['priceWithTax'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithoutDiscount()
	{
		return $this->prices['priceWithoutDiscount'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithoutDiscountWithTax()
	{
		return $this->prices['priceWithoutDiscountWithTax'];
	}

	/**
	 * @return float|null
	 */
	public function getEcoTaxValue()
	{
		return $this->prices['ecoTax'];
	}

	/**
 * @return string|null
 */
	public function getDesignation()
	{
		return $this->product->getCurrentLocalization()->getTitle();
	}

	/**
	 * @return string|null
	 */
	public function getCodeSKU()
	{
		$sku = $this->product->getSku();
		return $sku ? $sku->getCode() : null;
	}

	/**
	 * @return string|null
	 */
	public function getMinQuantity()
	{
		$sku = $this->product->getSku();
		return $sku ? $sku->getMinQuantity() : null;
	}

	public function toArray()
	{
		$array = [];
		$array['productId'] = $this->product->getId();
		$array['key'] = $array['productId'];
		$array['designation'] = $this->getDesignation();

		if (!is_array($this->stock))
		{
			$this->resetStock();
		}
		$array['stock'] = $this->stock;

		if (!is_array($this->prices))
		{
			$this->resetPrice();
		}
		$array['prices'] = $this->prices;
		return $array;
	}
}
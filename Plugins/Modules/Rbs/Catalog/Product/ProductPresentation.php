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
					if ($price && ($value = $price->getValue()) !== null)
					{
						$value *= $quantity;
						$isWithTax = $price->isWithTax();
						$taxCategories = $price->getTaxCategories();

						$this->prices['currencyCode'] = $currencyCode = $billingArea->getCurrencyCode();
						$zone = $this->commerceServices->getContext()->getZone();
						if ($zone)
						{
							if ($isWithTax)
							{
								$taxes = $priceManager->getTaxByValueWithTax($value, $taxCategories, $billingArea, $zone);
							}
							else
							{
								$taxes = $priceManager->getTaxByValue($value, $taxCategories, $billingArea, $zone);
							}
						}
						else
						{
							$taxes = null;
						}

						if ($isWithTax)
						{
							$this->prices['priceWithTax'] = $value;
							$this->prices['formattedPriceWithTax'] = $priceManager->formatValue($value, $currencyCode);
							if ($taxes) {
								$value = $priceManager->getValueWithoutTax($value, $taxes);
								$this->prices['price'] = $value;
								$this->prices['formattedPrice'] = $priceManager->formatValue($value, $currencyCode);
							}
						}
						else
						{
							$this->prices['price'] = $value;
							$this->prices['formattedPrice'] = $priceManager->formatValue($value, $currencyCode);
							if ($taxes)
							{
								$value = $priceManager->getValueWithTax($value, $taxes);
								$this->prices['priceWithTax'] = $value;
								$this->prices['formattedPriceWithTax'] = $priceManager->formatValue($value, $currencyCode);
							}
						}

						if (($oldValue = $price->getBasePriceValue()) !== null)
						{
							$oldValue *= $quantity;
							if ($zone)
							{
								if ($isWithTax)
								{
									$taxes = $priceManager->getTaxByValueWithTax($oldValue, $taxCategories, $billingArea, $zone);
								}
								else
								{
									$taxes = $priceManager->getTaxByValue($oldValue, $taxCategories, $billingArea, $zone);
								}
							}
							else
							{
								$taxes = null;
							}
							if ($isWithTax)
							{
								$this->prices['priceWithoutDiscountWithTax'] = $oldValue;
								$this->prices['formattedPriceWithoutDiscountWithTax'] = $priceManager->formatValue($oldValue, $currencyCode);
								if ($taxes) {
									$oldValue = $priceManager->getValueWithoutTax($oldValue, $taxes);
									$this->prices['priceWithoutDiscount'] = $oldValue;
									$this->prices['formattedPriceWithoutDiscount'] = $priceManager->formatValue($oldValue, $currencyCode);
								}
							}
							else
							{
								$this->prices['priceWithoutDiscount'] = $oldValue;
								$this->prices['formattedPriceWithoutDiscount'] = $priceManager->formatValue($oldValue, $currencyCode);
								if ($taxes) {

									$oldValue = $priceManager->getValueWithTax($oldValue, $taxes);
									$this->prices['priceWithoutDiscountWithTax'] = $oldValue;
									$this->prices['formattedPriceWithoutDiscountWithTax'] = $priceManager->formatValue($oldValue, $currencyCode);
								}
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
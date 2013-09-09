<?php
namespace Rbs\Catalog\Std;

use Rbs\Commerce\Interfaces\TaxApplication;

/**
* @name \Rbs\Catalog\Std\ProductPresentation
*/
class ProductPresentation
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $webStoreId
	 * @return \Rbs\Catalog\Std\ProductPresentation
	 */
	public function __construct(\Rbs\Commerce\Services\CommerceServices $commerceServices, \Rbs\Catalog\Documents\Product $product, $webStoreId)
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

	protected function resetPrice()
	{
		$this->prices['price'] = null;
		$this->prices['priceWithTax'] = null;
		$this->prices['priceWithoutDiscount'] = null;
		$this->prices['priceWithoutDiscountWithTax'] = null;
		$this->prices['ecoTax'] = null;
	}

	protected function resetStock()
	{
		$this->prices['level'] = null;
		$this->prices['threshold'] = null;
		$this->prices['thresholdClass'] = null;
		$this->prices['thresholdTitle'] = null;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function evaluate($quantity = 1)
	{
		$this->resetPrice();
		$this->resetStock();
		if ($this->product !== null && $quantity && $this->webStoreId)
		{
			$sku = $this->product->getSku();
			if ($sku)
			{
				$options = array('quantity' => ($sku->getMinQuantity() === null ? 1.0 : $sku->getMinQuantity()) * $quantity);

				$stm = $this->commerceServices->getStockManager();
				$level = $stm->getInventoryLevel($sku, $this->webStoreId);
				$threshold = $stm->getInventoryThreshold($sku, $this->webStoreId, $level);
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

				$price = $this->commerceServices->getPriceManager()->getPriceBySku($sku, $this->webStoreId, $options);
				if ($price)
				{
					$priceValue = $price->getValue();
					if ($priceValue !== null)
					{
						$tam = $this->commerceServices->getTaxManager();
						$this->prices['price'] += ($priceValue * $quantity);
						$taxApplication = $tam->getTaxByValue($priceValue, $price->getTaxCategories());
						if (count($taxApplication))
						{
							$tax = array_reduce($taxApplication, function ($result, TaxApplication $cartTax) use ($quantity)
							{
								return $result + $cartTax->getValue() * $quantity;
							}, 0.0);
							$this->prices['priceWithTax'] += ($priceValue * $quantity) + $tax;
						}

						$oldValue = $price->getValueWithoutDiscount();
						if ($oldValue !== null)
						{
							$this->prices['priceWithoutDiscount'] += ($oldValue * $quantity);
							$taxApplication = $tam->getTaxByValue($oldValue, $price->getTaxCategories());
							if (count($taxApplication))
							{
								$tax = array_reduce($taxApplication, function ($result, TaxApplication $cartTax) use ($quantity)
								{
									return $result + $cartTax->getValue() * $quantity;
								}, 0.0);
								$this->prices['priceWithoutDiscountWithTax'] += ($oldValue * $quantity) + $tax;
							}
						}

						if ($price->getEcoTax() !== null)
						{
							$this->prices['ecoTax'] += ($price->getEcoTax() * $quantity);
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
}
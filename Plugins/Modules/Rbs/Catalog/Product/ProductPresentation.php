<?php
namespace Rbs\Catalog\Product;

use Rbs\Commerce\Interfaces\TaxApplication;

/**
* @name \Rbs\Catalog\Std\ProductPresentation
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
		$this->prices['price'] = null;
		$this->prices['priceWithTax'] = null;
		$this->prices['priceWithoutDiscount'] = null;
		$this->prices['priceWithoutDiscountWithTax'] = null;
		$this->prices['ecoTax'] = null;
	}

	protected function resetStock()
	{
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
		if ($this->product !== null && $quantity && $this->webStoreId)
		{
			$sku = $this->product->getSku();
			if ($sku)
			{
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
				$this->stock['minQuantity'] = $sku->getMinQuantity();
				$this->stock['maxQuantity'] = $sku->getMaxQuantity() ? min($sku->getMaxQuantity(), $level) : $level;
				$this->stock['quantityIncrement'] = $sku->getQuantityIncrement() ? $sku->getQuantityIncrement() : 1;

				$price = $this->commerceServices->getPriceManager()->getPriceBySku($sku, $this->webStoreId);
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

						$basePrice = $price->getBasePrice();
						$oldValue = ($basePrice && $basePrice->activated()) ? $basePrice->getValue() : null;
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

	/**
	 * @return array
	 */
	public function getVariants()
	{
		$variants = array();
		if ($this->product->hasVariants())
		{
			$dm = $this->commerceServices->getDocumentManager();
			$query = $dm->getNewQuery('Rbs_Catalog_Product');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates(
				$pb->eq('variantGroup', $this->product->getVariantGroupId()),
				$pb->eq('variant', true),
				$pb->neq('sku', 0),
				$pb->published()
			);
			foreach($query->getDocuments() as $doc)
			{
				/* @var $doc \Rbs\Catalog\Documents\Product */
				$website = $doc->getCanonicalSection()->getWebsite();
				$lcid = $website->getLCID();
				$url = $website->getUrlManager($lcid)->getCanonicalByDocument($doc)->toString();
				$row = array('id' => $doc->getId(), 'url' => $url);
				$productPresentation = $doc->getPresentation($this->commerceServices, $this->webStoreId);
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$row['productPresentation'] = $productPresentation;
				}
				$variants[] = (new \Rbs\Catalog\Product\ProductItem($row))->setDocumentManager($dm);

			}
		}
		return $variants;
	}
}
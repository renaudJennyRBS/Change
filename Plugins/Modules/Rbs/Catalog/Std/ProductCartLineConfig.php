<?php
namespace Rbs\Catalog\Std;

use Rbs\Commerce\Interfaces\TaxApplication;

/**
* @name \Rbs\Catalog\Std\ProductCartLineConfig
*/
class ProductCartLineConfig implements \Rbs\Commerce\Interfaces\CartLineConfig
{
	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $designation;

	/**
	 * @var float
	 */
	protected $quantity;

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	protected $prices = array();

	/**
	 * @var ProductCartItemConfig[]
	 */
	protected $itemConfigArray = array();

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 */
	function __construct(\Rbs\Catalog\Documents\Product $product)
	{
		$this->designation = $product->getCurrentLocalization()->getTitle();
		$this->key = strval($product->getId());
		$this->options['productId'] = $product->getId();
		if ($product->getSku())
		{
			$this->itemConfigArray[] =  new ProductCartItemConfig($product->getSku());
		}
	}

	/**
	 * @param string $key
	 * @return $this
	 */
	public function setKey($key)
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $designation
	 * @return $this
	 */
	public function setDesignation($designation)
	{
		$this->designation = $designation;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @return ProductCartItemConfig[]
	 */
	public function getItemConfigArray()
	{
		return $this->itemConfigArray;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param array $options
	 * @return $this
	 */
	public function evaluatePrice($commerceServices, $options = array())
	{
		$this->prices = array();

		if (isset($options['quantity']))
		{
			$this->quantity = floatval($options['quantity']);
		}
		else
		{
			$this->quantity = null;
		}

		if (isset($this->options['webStoreId']))
		{
			$webStoreId =  intval($this->options['webStoreId']);
		}
		else
		{
			$webStoreId = null;
		}

		if (count($this->itemConfigArray))
		{
			$this->itemConfigArray = array_map(function(ProductCartItemConfig $item) {
				$item->setPriceValue(null);
				$item->setTaxApplication(array());
				return $item;
			}, $this->itemConfigArray);

			if ($this->quantity && $webStoreId)
			{
				/* @var $item ProductCartItemConfig */
				foreach ($this->itemConfigArray as $item)
				{
					$sku = $commerceServices->getStockManager()->getSkuByCode($item->getCodeSKU());
					if ($sku)
					{
						$price = $commerceServices->getPriceManager()->getPriceBySku($sku, $webStoreId);
						if ($price)
						{
							$priceValue = $price->getValue();
							$item->setPriceValue($priceValue);
							if ($priceValue)
							{
								$item->setTaxApplication($commerceServices->getTaxManager()->getTaxByValue($item->getPriceValue(), $price->getTaxCategories()));
							}

							$item->setOption('ecoTax', $price->getEcoTax());
							$oldValue = $price->getBasePrice();
							if ($oldValue !== null && $oldValue->activated())
							{
								$item->setOption('valueWithoutDiscount', $oldValue->getValue());
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
		if (!array_key_exists('getPriceValue', $this->prices))
		{
			$this->prices['getPriceValue'] = null;
			$quantity = $this->quantity;
			if ($quantity)
			{
				$this->prices['getPriceValue'] = array_reduce($this->itemConfigArray, function ($result, ProductCartItemConfig $item) use ($quantity)
				{
					if ($item->getPriceValue() !== null)
					{
						return $result + ($item->getPriceValue() * $quantity);
					}
					return $result;
				});
			}
		}
		return $this->prices['getPriceValue'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		if (!array_key_exists('getPriceValueWithTax', $this->prices))
		{
			$this->prices['getPriceValueWithTax'] = null;
			$quantity = $this->quantity;
			if ($quantity)
			{
				$this->prices['getPriceValueWithTax'] = array_reduce($this->itemConfigArray, function ($result, ProductCartItemConfig $item) use ($quantity)
				{
					if ($item->getPriceValue() !== null)
					{
						$tax = array_reduce($item->getTaxApplication(), function ($result, TaxApplication $cartTax) use ($quantity)
						{
							return $result + $cartTax->getValue() * $quantity;
						}, 0.0);
						return $result + ($item->getPriceValue() * $quantity) + $tax;
					}
					return $result;
				});
			}
		}
		return $this->prices['getPriceValueWithTax'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithoutDiscount()
	{
		if (!array_key_exists('getPriceValueWithoutDiscount', $this->prices))
		{
			$this->prices['getPriceValueWithoutDiscount'] = null;
			$quantity = $this->quantity;
			if ($quantity)
			{
				$this->prices['getPriceValueWithoutDiscount'] = array_reduce($this->itemConfigArray, function ($result, ProductCartItemConfig $item) use ($quantity)
				{
					if ($item->getOption('valueWithoutDiscount') !== null)
					{
						return $result + ($item->getOption('valueWithoutDiscount') * $quantity);
					}
					return $result;
				});
			}
		}
		return $this->prices['getPriceValueWithoutDiscount'];
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithoutDiscountWithTax()
	{
		if (!array_key_exists('getPriceValueWithoutDiscountWithTax', $this->prices))
		{
			$this->prices['getPriceValueWithoutDiscountWithTax'] = null;
			$quantity = $this->quantity;
			if ($quantity)
			{
				$this->prices['getPriceValueWithoutDiscountWithTax'] = array_reduce($this->itemConfigArray, function ($result, ProductCartItemConfig $item) use ($quantity)
				{
					if (($value = $item->getOption('valueWithoutDiscount')) !== null)
					{
						$tax = array_reduce($item->getTaxApplication(), function ($result, TaxApplication $cartTax) use ($quantity, $value)
						{
							return $result + $value * $cartTax->getRate() * $quantity;
						}, 0.0);
						return $result + ($value * $quantity) + $tax;
					}
					return $result;
				});
			}
		}
		return $this->prices['getPriceValueWithoutDiscountWithTax'];
	}

	/**
	 * @return float|null
	 */
	public function getEcoTaxValue()
	{
		if (!array_key_exists('getEcoTaxValue', $this->prices))
		{
			$this->prices['getEcoTaxValue'] = null;
			$quantity = $this->quantity;
			if ($quantity)
			{
				$this->prices['getEcoTaxValue'] = array_reduce($this->itemConfigArray, function ($result, ProductCartItemConfig $item) use ($quantity)
				{
					if ($item->getOption('ecoTax') !== null)
					{
						return $result + ($item->getOption('ecoTax') * $quantity);
					}
					return $result;
				});
			}
		}
		return $this->prices['getEcoTaxValue'];
	}
}
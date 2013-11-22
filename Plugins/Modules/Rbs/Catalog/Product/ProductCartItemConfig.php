<?php
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductCartItemConfig
 */
class ProductCartItemConfig implements \Rbs\Commerce\Interfaces\CartItemConfig
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var float
	 */
	protected $reservationQuantity;

	/**
	 * @var float
	 */
	protected $priceValue;

	/**
	 * @var \Rbs\Price\Std\TaxApplication[]
	 */
	protected $taxApplication = array();

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 */
	function __construct(\Rbs\Stock\Documents\Sku $sku)
	{
		$this->codeSKU = $sku->getCode();
		$this->setReservationQuantity($sku->getMinQuantity() === null ? 1.0 : $sku->getMinQuantity());
		$this->options['skuId'] = $sku->getId();
	}

	/**
	 * @param string $codeSKU
	 * @return $this
	 */
	public function setCodeSKU($codeSKU)
	{
		$this->codeSKU = $codeSKU;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param float|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = $reservationQuantity === null ? $reservationQuantity : floatval($reservationQuantity);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		$this->priceValue = $priceValue === null ? $priceValue : floatval($priceValue);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->priceValue;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\TaxApplication $taxApplication
	 * @return $this
	 */
	public function addTaxApplication(\Rbs\Commerce\Interfaces\TaxApplication $taxApplication = null)
	{
		if ($taxApplication !== null)
		{
			$this->taxApplication[] = $taxApplication;
		}
		return $this;
	}

	/**
	 * @param \Rbs\Price\Std\TaxApplication[] $taxApplication
	 * @return $this
	 */
	public function setTaxApplication(array $taxApplication)
	{
		$this->taxApplication = array();
		if (count($taxApplication))
		{
			foreach ($taxApplication as $tax)
			{
				$this->addTaxApplication($tax);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\TaxApplication[]
	 */
	public function getTaxApplication()
	{
		return $this->taxApplication;
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
		if ($value === null)
		{
			unset($this->options[$name]);
		}
		else
		{
			$this->options[$name] = $value;
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getOption($name, $defaultValue = null)
	{
		return isset($this->options[$name]) ? $this->options[$name] : $defaultValue;
	}
}
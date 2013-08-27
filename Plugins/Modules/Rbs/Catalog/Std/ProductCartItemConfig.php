<?php
namespace Rbs\Catalog\Std;

/**
* @name \Rbs\Catalog\Std\ProductCartItemConfig
*/
class ProductCartItemConfig implements  \Rbs\Commerce\Interfaces\CartItemConfig
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var float
	 */
	protected $reservationQuantity = 0.0;

	/**
	 * @var float
	 */
	protected $priceValue = 0.0;

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
		$this->reservationQuantity = floatval(max(1, $sku->getMinQuantity()));
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
	 * @param float $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = floatval($reservationQuantity);
		return $this;
	}

	/**
	 * @return float
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param float $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		$this->priceValue = $priceValue;
		return $this;
	}

	/**
	 * @return float
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
		$this->options[$name] = $value;
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
<?php
namespace ChangeTests\Modules\Commerce\Cart\Assets;

/**
* @name \ChangeTests\Modules\Commerce\Cart\Assets\TestCartItemConfig
*/
class TestCartItemConfig implements  \Rbs\Commerce\Interfaces\CartItemConfig
{
	/**
	 * @var string
	 */
	public $codeSKU;

	/**
	 * @var float
	 */
	public $reservationQuantity = 0.0;

	/**
	 * @var float
	 */
	public $priceValue = 0.0;

	/**
	 * @var \Rbs\Price\Std\TaxApplication[]
	 */
	public $taxApplication = array();

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @param $codeSKU
	 * @param $reservationQuantity
	 * @param $priceValue
	 * @param $taxApplication
	 * @param $options
	 */
	function __construct($codeSKU, $reservationQuantity, $priceValue, $taxApplication, $options)
	{
		$this->codeSKU = $codeSKU;
		$this->reservationQuantity = $reservationQuantity;
		$this->priceValue = $priceValue;
		$this->taxApplication = $taxApplication;
		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}
	/**
	 * @return float
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
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

	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		return $this;
	}

	public function getOption($name, $defaultValue = null)
	{
		return isset($this->options[$name]) ? $this->options[$name] : $defaultValue;
	}

	/**
	 * @param float $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = $reservationQuantity;
		return $this;
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
}
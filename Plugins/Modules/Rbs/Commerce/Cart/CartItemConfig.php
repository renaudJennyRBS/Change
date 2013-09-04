<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Services\CommerceServices;

/**
* @name \Rbs\Commerce\Cart\CartItemConfig
*/
class CartItemConfig implements \Rbs\Commerce\Interfaces\CartItemConfig
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer
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
	 * @param CommerceServices $commerceServices
	 * @param array $array
	 */
	function __construct(CommerceServices $commerceServices, array $array = array())
	{
		$this->codeSKU = isset($array['codeSKU']) ? $array['codeSKU'] : null;
		$this->reservationQuantity = isset($array['reservationQuantity']) ? $array['reservationQuantity'] : null;
		$this->priceValue = isset($array['priceValue']) ? $array['priceValue'] : null;

		if (isset($array['options']) && is_array($array['options']))
		{
			$this->options = $array['options'];
		}

		if (isset($array['cartTaxes']) && is_array($array['cartTaxes']))
		{
			foreach ($array['cartTaxes'] as $cartTax)
			{
				$tax = isset($cartTax['tax']) ? $cartTax['tax'] : null;
				if ($tax)
				{
					$tax = $commerceServices->getTaxManager()->getTaxByCode($tax);
				}

				if ($tax instanceof \Rbs\Commerce\Interfaces\Tax)
				{
					$category = isset($cartTax['category']) ? $cartTax['category'] : null;
					$zone = isset($cartTax['zone']) ? $cartTax['zone'] : null;
					$rate = isset($cartTax['rate']) ? $cartTax['rate'] : 0.0;
					$value = isset($cartTax['value']) ? $cartTax['value'] : null;

					$taxApplication = new \Rbs\Price\Std\TaxApplication($tax, $category, $zone, $rate);
					$taxApplication->setValue($value);
					$this->taxApplication[] = $taxApplication;
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param integer|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = $reservationQuantity === null ? $reservationQuantity : intval($reservationQuantity);
		return $this;
	}

	/**
	 * @return integer|null
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
	 * @return \Rbs\Commerce\Interfaces\TaxApplication|\Rbs\Commerce\Interfaces\TaxApplication[]
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
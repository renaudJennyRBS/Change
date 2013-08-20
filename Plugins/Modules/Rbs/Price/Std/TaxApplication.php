<?php
namespace Rbs\Price\Std;

use  Rbs\Commerce\Interfaces\TaxApplication as TaxApplicationInterfaces;

/**
 * @name \Rbs\Price\Std\TaxApplication
 */
class TaxApplication implements TaxApplicationInterfaces
{
	/**
	 * @var \Rbs\Commerce\Interfaces\Tax
	 */
	protected $tax;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var string
	 */
	protected $category;

	/**
	 * @var float
	 */
	protected $value = 0.0;

	/**
	 * @var float
	 */
	protected $rate = 0.0;

	/**
	 * @param \Rbs\Commerce\Interfaces\Tax $tax
	 * @param string $category
	 * @param string $zone
	 * @param float $rate
	 */
	function __construct($tax, $category, $zone, $rate = 0.0)
	{
		$this->tax = $tax;
		$this->category = $category;
		$this->zone = $zone;
		$this->rate = $rate;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\Tax
	 */
	public function getTax()
	{
		return $this->tax;
	}

	/**
	 * @return string
	 */
	public function getTaxCode()
	{
		return $this->tax->getCode();
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		return $this->zone;
	}

	/**
	 * @return string
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * @param float $rate
	 * @return $this
	 */
	public function setRate($rate)
	{
		$this->rate = $rate;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getRate()
	{
		return $this->rate;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
		return $this->value;
	}
}
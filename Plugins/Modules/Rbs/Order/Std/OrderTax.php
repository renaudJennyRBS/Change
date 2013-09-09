<?php
namespace Rbs\Order\Std;

/**
* @name \Rbs\Order\Std\OrderTax
*/
class OrderTax
{
	/**
	 * @var string
	 */
	protected $taxCode;

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
	 * @param array $config
	 */
	function __construct(array $config = null)
	{
		if ($config)
		{
			$this->fromArray($config);
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'taxCode': $this->taxCode = $value; break;
				case 'zone': $this->zone = $value; break;
				case 'category': $this->category = $value; break;
				case 'value': $this->value = $value; break;
				case 'rate': $this->rate = $value; break;
			}
		}
		return $this;
	}

	/**
	 * @param string $taxCode
	 * @return $this
	 */
	public function setTaxCode($taxCode)
	{
		$this->taxCode = $taxCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTaxCode()
	{
		return $this->taxCode;
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		return $this->zone;
	}

	/**
	 * @param string $category
	 * @return $this
	 */
	public function setCategory($category)
	{
		$this->category = $category;
		return $this;
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
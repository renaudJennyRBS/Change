<?php
namespace Rbs\Price\Tax;

/**
 * @name \Rbs\Price\Tax\TaxApplication
 */
class TaxApplication
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
	 * @param \Rbs\Price\Tax\TaxInterface|array|string $taxCode
	 * @param string $category
	 * @param string $zone
	 * @param float $rate
	 */
	function __construct($taxCode, $category = null, $zone = null, $rate = 0.0)
	{
		if ($taxCode  instanceof \Rbs\Price\Tax\TaxInterface)
		{
			$taxCode = $taxCode->getCode();
		}
		elseif (is_array($taxCode) && isset($taxCode['taxCode']))
		{
			if (isset($taxCode['category']))
			{
				$category = $taxCode['category'];
			}
			if (isset($taxCode['zone']))
			{
				$zone = $taxCode['zone'];
			}
			if (isset($taxCode['rate']))
			{
				$rate = $taxCode['rate'];
			}
			$taxCode = $taxCode['taxCode'];
		}
		$this->taxCode = $taxCode;
		$this->category = $category;
		$this->zone = $zone;
		$this->rate = $rate;
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
	 * @param float $add
	 * @return $this
	 */
	public function addValue($add)
	{
		$this->value += $add;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param TaxApplication $taxApplication
	 */
	public function addTaxApplication($taxApplication)
	{
		if ($this->isSameTax($taxApplication))
		{
			$this->addValue($taxApplication->getValue());
		}
	}
	/**
	 * @return string
	 */
	public function getTaxKey()
	{
		return $this->taxCode . '|' . $this->zone . '|' . $this->category;
	}

	/**
	 * @param TaxApplication $taxApplication
	 * @return bool
	 */
	public function isSameTax($taxApplication)
	{
		return ($taxApplication instanceof TaxApplication && $this->getTaxKey() ==  $taxApplication->getTaxKey());
	}

	public function toArray()
	{
		return ['taxCode' => $this->getTaxCode(), 'zone' => $this->getZone(), 'category' => $this->getCategory(),
			'rate' => $this->getRate(), 'value' => $this->getValue()];
	}
}
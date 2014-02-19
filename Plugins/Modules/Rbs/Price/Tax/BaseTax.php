<?php
namespace Rbs\Price\Tax;

/**
 * @name \Rbs\Price\Tax\BaseTax
 */
class BaseTax implements TaxInterface
{
	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var boolean
	 */
	protected $cascading = false;

	/**
	 * @var string
	 */
	protected $rounding = 't';

	/**
	 * @var array
	 */
	protected $rates = [];

	/**
	 * @param array|\Rbs\Price\Tax\TaxInterface|null $tax
	 */
	function __construct($tax = null)
	{
		if (is_array($tax))
		{
			$this->fromArray($tax);
		}
		else if ($tax instanceof TaxInterface)
		{
			$this->fromArray($tax->toArray());
		}
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = ($code !== null) ? strval($code) : null;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param array $rates
	 * @return $this
	 */
	public function setRates($rates)
	{
		$this->rates = is_array($rates) ? $rates : [];
		return $this;
	}

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone)
	{
		return isset($this->rates[$category][$zone]) ? $this->rates[$category][$zone] : 0.0;
	}

	/**
	 * @param boolean $cascading
	 * @return $this
	 */
	public function setCascading($cascading)
	{
		$this->cascading = ($cascading == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCascading()
	{
		return $this->cascading;
	}

	/**
	 * @param string $rounding
	 * @return $this
	 */
	public function setRounding($rounding)
	{
		if ($rounding === static::ROUNDING_TOTAL || $rounding === static::ROUNDING_ROW || $rounding === static::ROUNDING_UNIT)
		{
			$this->rounding = $rounding;
		}
		else
		{
			$this->rounding = static::ROUNDING_TOTAL;
		}

		return $this;
	}

	/**
	 * Return t => total, l => row, u => unit
	 * @return string
	 */
	public function getRounding()
	{
		return $this->rounding;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$array += ['code' => null, 'rounding' => static::ROUNDING_TOTAL, 'cascading' => false, 'rates' => []];
		$this->setCode($array['code']);
		$this->setRounding($array['rounding']);
		$this->setCascading($array['cascading']);
		$this->setRates($array['rates']);
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
}
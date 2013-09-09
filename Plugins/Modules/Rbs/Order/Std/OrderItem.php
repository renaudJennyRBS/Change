<?php
namespace Rbs\Order\Std;

/**
* @name \Rbs\Order\Std\OrderItem
*/
class OrderItem
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer|null
	 */
	protected $quantity;

	/**
	 * @var float|null
	 */
	protected $priceValue;

	/**
	 * @var OrderTax[]
	 */
	protected $taxes = array();

	/**
	 * @var array
	 */
	protected $options;

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
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'codeSKU': $this->codeSKU = $value; break;
				case 'quantity': $this->quantity = $value; break;
				case 'priceValue': $this->priceValue = $value; break;
				case 'taxes':
					if (is_array($value))
					{
						$this->taxes = array_map(function($tax) { return new OrderTax($tax);}, $value);
					}
					else
					{
						$this->taxes = array();
					}
					break;
				case 'options':
					$this->options = (is_array($value)) ? $value : array();
					break;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = get_object_vars($this);
		$array['taxes'] = array_map(function(OrderTax $tax) {return $tax->toArray();}, $array['taxes']);
		return $array;
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
	 * @param int|null $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		$this->priceValue = $priceValue;
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
	 * @param \Rbs\Order\Std\OrderTax[] $taxes
	 * @return $this
	 */
	public function setTaxes($taxes)
	{
		$this->taxes = $taxes;
		return $this;
	}

	/**
	 * @return \Rbs\Order\Std\OrderTax[]
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions($options)
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
}
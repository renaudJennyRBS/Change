<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartLineConfig
 */
class CartLineConfig implements \Rbs\Commerce\Interfaces\CartLineConfig
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
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	protected $itemConfigArray = array();

	/**
	 * @var float
	 */
	protected $priceValue;

	/**
	 * @var float
	 */
	protected $priceValueWithTax;

	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param array $array
	 */
	function __construct(\Rbs\Commerce\CommerceServices $commerceServices, array $array = array())
	{
		$this->key = isset($array['key']) ? $array['key'] : null;
		$this->quantity = isset($array['quantity']) ? $array['quantity'] : null;
		$this->designation = isset($array['designation']) ? $array['designation'] : null;

		$this->priceValue = isset($array['priceValue']) ? $array['priceValue'] : null;
		$this->priceValueWithTax = isset($array['priceValueWithTax']) ? $array['priceValueWithTax'] : null;

		if (isset($array['options']) && is_array($array['options']))
		{
			$this->options = $array['options'];
		}

		if (isset($array['items']) && is_array($array['items']))
		{
			foreach ($array['items'] as $item)
			{
				$this->itemConfigArray[] = new CartItemConfig($commerceServices, $item);
			}
		}
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItemConfig[]
	 */
	public function getItemConfigArray()
	{
		return $this->itemConfigArray;
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
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @throws \LogicException
	 * @return $this
	 */
	public function evaluatePrice($commerceServices)
	{
		throw new \LogicException('Not implemented');
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->priceValue;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		return $this->priceValueWithTax;
	}

	/**
	 * @return integer|null
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}
}
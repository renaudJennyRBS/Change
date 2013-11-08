<?php
namespace ChangeTests\Modules\Commerce\Cart\Assets;

/**
* @name \ChangeTests\Modules\Commerce\Cart\Assets\TestCartLineConfig
*/
class TestCartLineConfig implements \Rbs\Commerce\Interfaces\CartLineConfig
{
	/**
	 * @var string
	 */
	public $key;

	/**
	 * @var string
	 */
	public $designation;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var \Rbs\Commerce\Interfaces\CartItemConfig[]
	 */
	public $itemConfigArray = array();

	/**
	 * @param string $key
	 * @param string $designation
	 * @param \Rbs\Commerce\Interfaces\CartItemConfig[] $itemConfigArray
	 * @param array $options
	 */
	function __construct($key, $designation, $itemConfigArray, $options)
	{
		$this->key = $key;
		$this->designation = $designation;
		$this->itemConfigArray = $itemConfigArray;
		$this->options = $options;
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

	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @return $this
	 */
	public function evaluatePrice($commerceServices)
	{
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return null;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		return null;
	}
}
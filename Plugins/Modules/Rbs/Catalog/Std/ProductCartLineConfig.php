<?php
namespace Rbs\Catalog\Std;

/**
* @name \Rbs\Catalog\Std\ProductCartLineConfig
*/
class ProductCartLineConfig implements \Rbs\Commerce\Interfaces\CartLineConfig
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
	 * @var ProductCartItemConfig[]
	 */
	protected $itemConfigArray = array();

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 */
	function __construct(\Rbs\Catalog\Documents\Product $product )
	{
		$this->designation = $product->getTitle();
		$this->key = strval($product->getId());
		$this->options['productId'] = $product->getId();
		if ($product->getSku())
		{
			$this->itemConfigArray[] =  new ProductCartItemConfig($product->getSku());
		}
	}

	/**
	 * @param string $key
	 * @return $this
	 */
	public function setKey($key)
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $designation
	 * @return $this
	 */
	public function setDesignation($designation)
	{
		$this->designation = $designation;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @return ProductCartItemConfig[]
	 */
	public function getItemConfigArray()
	{
		return $this->itemConfigArray;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options)
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
}
<?php
namespace Rbs\Commerce\Http\Rest\Result;

use Change\Http\Rest\Result\Links;
use Change\Http\Result;

/**
 * @name \Rbs\Commerce\Http\Rest\Result\CartResult
 */
class CartResult extends Result
{
	/**
	 * @var Links
	 */
	protected $links;

	/**
	 * @var array
	 */
	protected $cart;


	function __construct()
	{
		$this->links = new Links();
		parent::__construct();
	}

	/**
	 * @return \Change\Http\Rest\Result\Links
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * @param \Change\Http\Rest\Result\Link|array $link
	 */
	public function addLink($link)
	{
		$this->links[] = $link;
	}

	/**
	 * @param array $cartArray
	 */
	public function setCart($cartArray)
	{
		$this->cart = $cartArray;
	}

	/**
	 * @return array
	 */
	public function getCart()
	{
		return $this->cart;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value)
	{
		$this->cart[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array =  array();
		$links = $this->getLinks();
		$array['links'] = $links->toArray();
		$array['cart'] = $this->cart;
		return $array;
	}
}
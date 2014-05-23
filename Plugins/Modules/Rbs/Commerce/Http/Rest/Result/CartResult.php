<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Rest\Result;

use Change\Http\Rest\V1\Links;
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
	 * @return \Change\Http\Rest\V1\Links
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * @param \Change\Http\Rest\V1\Link|array $link
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
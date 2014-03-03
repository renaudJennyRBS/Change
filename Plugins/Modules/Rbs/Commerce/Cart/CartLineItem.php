<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineItemInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLineItem
 */
class CartLineItem extends \Rbs\Commerce\Std\BaseLineItem implements LineItemInterface, \Serializable
{

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @param string|array|LineItemInterface $codeSKU
	 * @param CartManager $cartManager
	 */
	function __construct($codeSKU, \Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;
		if (is_array($codeSKU))
		{
			$this->fromArray($codeSKU);
		}
		else if ($codeSKU instanceof LineItemInterface)
		{
			$this->fromLineItem($codeSKU);
		}
		else
		{
			$this->setCodeSKU($codeSKU);
		}
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	protected function getCartManager()
	{
		return $this->cartManager;
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @return $this
	 */
	public function setCartManager(\Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;
		if ($this->serializedData && $cartManager)
		{
			$this->restoreSerializedData();
		}
		return $this;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'price' => $this->price,
			'options' => $this->options);
		return serialize($this->getCartManager()->getSerializableValue($serializedData));
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->serializedData = unserialize($serialized);
	}

	/**
	 * @return array
	 */
	protected function restoreSerializedData()
	{
		$serializedData = $this->getCartManager()->restoreSerializableValue($this->serializedData);
		$this->serializedData = null;

		$this->codeSKU = $serializedData['codeSKU'];
		$this->reservationQuantity = $serializedData['reservationQuantity'];
		$this->price = $serializedData['price'];
		$this->options = $serializedData['options'];

		return $serializedData;
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order;

/**
 * @name \Rbs\Order\OrderPresentation
 */
class OrderPresentation
{
	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $currencyCode;

	/**
	 * @var \DateTime
	 */
	protected $date;

	/**
	 * @var integer
	 */
	protected $itemCount;

	/**
	 * @var float
	 */
	protected $totalAmount;

	/**
	 * @var float
	 */
	protected $totalAmountWithTaxes;

	/**
	 * @param \Rbs\Order\Documents\Order|\Rbs\Commerce\Cart\Cart|array $order
	 */
	function __construct($order)
	{
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$this->fromOrder($order);
		}
		elseif ($order instanceof \Rbs\Commerce\Cart\Cart)
		{
			$this->fromCart($order);
		}
		else
		{
			$this->fromArray($order);
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Order $order
	 */
	protected function fromOrder($order)
	{
		$this->setId($order->getId());
		$this->setLabel($order->getLabel());
		$this->setCurrencyCode($order->getCurrencyCode());
		$this->setDate($order->getCreationDate());
		$this->setTotalAmount($order->getTotalAmount());
		$this->setTotalAmountWithTaxes($order->getTotalAmountWithTaxes());
		$itemCount = 0;
		foreach ($order->getLines() as $line)
		{
			$itemCount += $line->getQuantity();
		}
		$this->setItemCount($itemCount);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	protected function fromCart($cart)
	{
		$this->setId(0);
		$this->setLabel(null);
		$this->setCurrencyCode($cart->getCurrencyCode());
		$this->setDate($cart->lastUpdate());
		$this->setTotalAmount($cart->getTotalAmount());
		$this->setTotalAmountWithTaxes($cart->getTotalAmountWithTaxes());
		$itemCount = 0;
		foreach ($cart->getLines() as $line)
		{
			$itemCount += $line->getQuantity();
		}
		$this->setItemCount($itemCount);
	}

	/**
	 * @param array $array
	 */
	protected function fromArray($array)
	{
		$this->setId($array['id']);
		$this->setLabel($array['label']);
		$this->setCurrencyCode($array['currencyCode']);
		$this->setDate($array['date']);
		$this->setTotalAmount($array['totalAmount']);
		$this->setTotalAmountWithTaxes($array['totalAmountWithTaxes']);
		$this->setItemCount($array['itemCount']);
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $currencyCode
	 * @return $this
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->currencyCode = $currencyCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @param \DateTime $date
	 * @return $this
	 */
	public function setDate(\DateTime $date = null)
	{
		$this->date = $date;
		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param integer $itemCount
	 * @return $this
	 */
	public function setItemCount($itemCount)
	{
		$this->itemCount = $itemCount;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getItemCount()
	{
		return $this->itemCount;
	}

	/**
	 * @param float $amount
	 * @return $this
	 */
	public function setTotalAmount($amount)
	{
		$this->totalAmount = $amount;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getTotalAmount()
	{
		return $this->totalAmount;
	}

	/**
	 * @param float $amount
	 * @return $this
	 */
	public function setTotalAmountWithTaxes($amount)
	{
		$this->totalAmountWithTaxes = $amount;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getTotalAmountWithTaxes()
	{
		return $this->totalAmountWithTaxes;
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Presentation;

/**
 * @name \Rbs\Order\Presentation\OrderPresentation
 */
class OrderPresentation
{
	/**
	 * @var array
	 */
	protected $context;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var array|null
	 */
	protected $statusInfo;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $cartIdentifier;

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
	protected $linesAmount;

	/**
	 * @var float
	 */
	protected $linesAmountWithTaxes;

	/**
	 * @var float
	 */
	protected $totalAmount;

	/**
	 * @var float
	 */
	protected $totalAmountWithTaxes;

	/**
	 * @var float
	 */
	protected $paymentAmountWithTaxes;

	/**
	 * @var string
	 */
	protected $processingStatus;

	/**
	 * @var string
	 */
	protected $email;

	/**
	 * @var \Rbs\Geo\Address\BaseAddress|null
	 */
	protected $address;

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $lines = [];

	/**
	 * @var \Rbs\Commerce\Process\ShippingModeInterface[]
	 */
	protected $shippingModes = [];

	/**
	 * @var string[]
	 */
	protected $shippingModesStatuses = [];

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $fees = [];

	/**
	 * @var \Rbs\Commerce\Process\BaseCoupon[]
	 */
	protected $coupons = [];

	/**
	 * @var \Rbs\Commerce\Process\BaseDiscount[]
	 */
	protected $discounts = [];

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $totalTaxes = [];

	/**
	 * @var \Rbs\Commerce\Process\BaseCreditNote[]
	 */
	protected $creditNotes = [];

	/**
	 * @var \Rbs\Order\Presentation\TransactionPresentation[]
	 */
	protected $transactions = [];

	/**
	 * @var \Rbs\Order\Presentation\ShipmentPresentation[]
	 */
	protected $shipments = [];

	/**
	 * @param \Rbs\Order\Documents\Order|\Rbs\Commerce\Cart\Cart|array $order
	 */
	public function __construct($order)
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
		$this->setCode($order->getCode());
		$this->setCartIdentifier($order->getContext()->get('cartIdentifier'));
		$this->setCurrencyCode($order->getCurrencyCode());
		$this->setDate($order->getCreationDate());
		$this->setLinesAmount($order->getLinesAmount());
		$this->setLinesAmountWithTaxes($order->getLinesAmountWithTaxes());
		$this->setTotalAmount($order->getTotalAmount());
		$this->setTotalAmountWithTaxes($order->getTotalAmountWithTaxes());
		$this->setPaymentAmountWithTaxes($order->getPaymentAmountWithTaxes());
		$itemCount = 0;
		foreach ($order->getLines() as $line)
		{
			$itemCount += $line->getQuantity();
		}
		$this->setItemCount($itemCount);
		$this->setProcessingStatus($order->getProcessingStatus());
		$this->setEmail($order->getEmail());
		$this->setAddress($order->getAddress());
		$this->setLines($order->getLines());
		$this->setShippingModes($order->getShippingModes());
		$this->setFees($order->getFees());
		$this->setCoupons($order->getCoupons());
		$this->setDiscounts($order->getDiscounts());
		$this->setTotalTaxes($order->getTotalTaxes());
		$this->setCreditNotes($order->getCreditNotes());
		$this->setContext($order->getContext()->toArray());
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	protected function fromCart($cart)
	{
		$this->setId(0);
		$this->setCode(null);
		$this->setCartIdentifier($cart->getIdentifier());
		$this->setCurrencyCode($cart->getCurrencyCode());
		$this->setDate($cart->lastUpdate());
		$this->setLinesAmount($cart->getLinesAmount());
		$this->setLinesAmountWithTaxes($cart->getLinesAmountWithTaxes());
		$this->setTotalAmount($cart->getTotalAmount());
		$this->setTotalAmountWithTaxes($cart->getTotalAmountWithTaxes());
		$this->setPaymentAmountWithTaxes($cart->getPaymentAmountWithTaxes());
		$itemCount = 0;
		foreach ($cart->getLines() as $line)
		{
			$itemCount += $line->getQuantity();
		}
		$this->setItemCount($itemCount);
		$this->setProcessingStatus('processing');
		$this->setEmail($cart->getEmail());
		$this->setAddress($cart->getAddress());
		$this->setLines($cart->getLines());
		$this->setShippingModes($cart->getShippingModes());
		$statuses = array();
		foreach ($this->getShippingModes() as $shippingMode)
		{
			$statuses[$shippingMode->getId()] = 'noShipment';
		}
		$this->setShippingModesStatuses($statuses);
		$this->setFees($cart->getFees());
		$this->setCoupons($cart->getCoupons());
		$this->setDiscounts($cart->getDiscounts());
		$this->setTotalTaxes($cart->getTotalTaxes());
		$this->setCreditNotes($cart->getCreditNotes());
		$this->setContext($cart->getContext()->toArray());
	}

	/**
	 * @param array $array
	 */
	protected function fromArray($array)
	{
		if (isset($array['code']))
		{
			$this->setCode($array['code']);
		}
		if (isset($array['id']))
		{
			$this->setId($array['id']);
		}
		if (isset($array['statusInfo']))
		{
			$this->setStatusInfo($array['statusInfo']);
		}
		if (isset($array['cartIdentifier']))
		{
			$this->setCartIdentifier($array['cartIdentifier']);
		}
		if (isset($array['currencyCode']))
		{
			$this->setCurrencyCode($array['currencyCode']);
		}
		if (isset($array['date']))
		{
			$this->setDate($array['date']);
		}
		if (isset($array['linesAmount']))
		{
			$this->setLinesAmount($array['linesAmount']);
		}
		if (isset($array['linesAmountWithTaxes']))
		{
			$this->setLinesAmountWithTaxes($array['linesAmountWithTaxes']);
		}
		if (isset($array['totalAmount']))
		{
			$this->setTotalAmount($array['totalAmount']);
		}
		if (isset($array['totalAmountWithTaxes']))
		{
			$this->setTotalAmountWithTaxes($array['totalAmountWithTaxes']);
		}
		if (isset($array['paymentAmountWithTaxes']))
		{
			$this->setPaymentAmountWithTaxes($array['paymentAmountWithTaxes']);
		}
		if (isset($array['itemCount']))
		{
			$this->setItemCount($array['itemCount']);
		}
		if (isset($array['processingStatus']))
		{
			$this->setProcessingStatus($array['processingStatus']);
		}
		if (isset($array['email']))
		{
			$this->setEmail($array['email']);
		}
		if (isset($array['address']))
		{
			$this->setAddress($array['address']);
		}
		if (isset($array['lines']))
		{
			$this->setLines($array['lines']);
		}
		if (isset($array['shippingModes']))
		{
			$this->setShippingModes($array['shippingModes']);
		}
		if (isset($array['shippingModesStatuses']))
		{
			$this->setShippingModesStatuses($array['shippingModesStatuses']);
		}
		else
		{
			$statuses = array();
			foreach ($this->getShippingModes() as $shippingMode)
			{
				$statuses[$shippingMode->getId()] = 'noShipment';
			}
			$this->setShippingModesStatuses($statuses);
		}
		if (isset($array['fees']))
		{
			$this->setFees($array['fees']);
		}
		if (isset($array['coupons']))
		{
			$this->setCoupons($array['coupons']);
		}
		if (isset($array['discounts']))
		{
			$this->setDiscounts($array['discounts']);
		}
		if (isset($array['totalTaxes']))
		{
			$this->setTotalTaxes($array['totalTaxes']);
		}
		if (isset($array['creditNotes']))
		{
			$this->setCreditNotes($array['creditNotes']);
		}
		if (isset($array['transactions']))
		{
			$this->setTransactions($array['transactions']);
		}
		if (isset($array['context']))
		{
			$this->setContext($array['context']);
		}
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setCode($label)
	{
		$this->code = $label;
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
	 * @param string $cartIdentifier
	 * @return $this
	 */
	public function setCartIdentifier($cartIdentifier)
	{
		$this->cartIdentifier = $cartIdentifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCartIdentifier()
	{
		return $this->cartIdentifier;
	}

	/**
	 * @param array $statusInfo
	 * @return $this
	 */
	public function setStatusInfo($statusInfo)
	{
		$this->statusInfo = $statusInfo;
		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getStatusInfo()
	{
		return $this->statusInfo;
	}

	/**
	 * @return string
	 */
	public function getStatusTitle()
	{
		if (is_array($this->statusInfo) && isset($this->statusInfo['title']))
		{
			return $this->statusInfo['title'];
		}
		return '';
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
	 * @param float $linesAmount
	 * @return $this
	 */
	public function setLinesAmount($linesAmount)
	{
		$this->linesAmount = $linesAmount;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getLinesAmount()
	{
		return $this->linesAmount;
	}

	/**
	 * @param float $linesAmountWithTaxes
	 * @return $this
	 */
	public function setLinesAmountWithTaxes($linesAmountWithTaxes)
	{
		$this->linesAmountWithTaxes = $linesAmountWithTaxes;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getLinesAmountWithTaxes()
	{
		return $this->linesAmountWithTaxes;
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

	/**
	 * @return float|null
	 */
	public function getTotalCreditNotesAmount()
	{
		if (count($this->creditNotes))
		{
			$totalCreditNotesAmount = 0.0;
			foreach ($this->creditNotes as $creditNote)
			{
				$totalCreditNotesAmount += $creditNote->getAmount();
			}
			return $totalCreditNotesAmount;
		}
		return null;
	}

	/**
	 * @param float $paymentAmountWithTaxes
	 * @return $this
	 */
	public function setPaymentAmountWithTaxes($paymentAmountWithTaxes)
	{
		$this->paymentAmountWithTaxes = $paymentAmountWithTaxes;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getPaymentAmountWithTaxes()
	{
		return $this->paymentAmountWithTaxes;
	}

	/**
	 * @param string $status
	 * @return $this
	 */
	public function setProcessingStatus($status)
	{
		$this->processingStatus = $status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProcessingStatus()
	{
		return $this->processingStatus;
	}

	/**
	 * @param string $email
	 * @return $this
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		if (isset($address))
		{
			$this->address = new \Rbs\Geo\Address\BaseAddress($address);
		}
		else
		{
			$this->address = null;
		}
		return $this;
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param \Rbs\Order\OrderLine[]|\Rbs\Commerce\Cart\CartLine[]|array[] $lines
	 * @return $this
	 */
	public function setLines($lines)
	{
		$this->lines = [];
		if (is_array($lines))
		{
			foreach ($lines as $line)
			{
				$this->lines[] = new \Rbs\Order\OrderLine($line);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Order\OrderLine[]
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * @param \Rbs\Commerce\Process\ShippingModeInterface[]|array[] $shippingModes
	 * @return $this
	 */
	public function setShippingModes($shippingModes)
	{
		$this->shippingModes = [];
		if (is_array($shippingModes))
		{
			foreach ($shippingModes as $shippingMode)
			{
				$this->shippingModes[] = new \Rbs\Commerce\Process\BaseShippingMode($shippingMode);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\BaseShippingMode[]
	 */
	public function getShippingModes()
	{
		return $this->shippingModes;
	}

	/**
	 * @param string[] $shippingModesStatuses
	 * @return $this
	 */
	public function setShippingModesStatuses($shippingModesStatuses)
	{
		$this->shippingModesStatuses = $shippingModesStatuses;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getShippingModesStatuses()
	{
		return $this->shippingModesStatuses;
	}

	/**
	 * @param \Rbs\Order\OrderLine[]|\Rbs\Commerce\Cart\CartLine[]|array[] $fees
	 * @return $this
	 */
	public function setFees($fees)
	{
		$this->fees = [];
		if (is_array($fees))
		{
			foreach ($fees as $fee)
			{
				$this->fees[] = new \Rbs\Order\OrderLine($fee);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Order\OrderLine[]
	 */
	public function getFees()
	{
		return $this->fees;
	}

	/**
	 * @param \Rbs\Commerce\Process\CouponInterface[]|array[] $coupons
	 * @return $this
	 */
	public function setCoupons($coupons)
	{
		$this->coupons = [];
		if (is_array($coupons))
		{
			foreach ($coupons as $coupon)
			{
				$this->coupons[] = new \Rbs\Commerce\Process\BaseCoupon($coupon);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\BaseCoupon[]
	 */
	public function getCoupons()
	{
		return $this->coupons;
	}

	/**
	 * @param \Rbs\Commerce\Process\BaseDiscount[]|array[] $discounts
	 * @return $this
	 */
	public function setDiscounts($discounts)
	{
		$this->discounts = [];
		if (is_array($discounts))
		{
			foreach ($discounts as $discount)
			{
				$this->discounts[] = new \Rbs\Commerce\Process\BaseDiscount($discount);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\BaseDiscount[]
	 */
	public function getDiscounts()
	{
		return $this->discounts;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[]|array[] $totalTaxes
	 * @return $this
	 */
	public function setTotalTaxes($totalTaxes)
	{
		$this->totalTaxes = [];
		if (is_array($totalTaxes))
		{
			foreach ($totalTaxes as $totalTax)
			{
				if ($totalTax instanceof \Rbs\Price\Tax\TaxApplication)
				{
					$totalTax = $totalTax->toArray();
				}
				$this->totalTaxes[] = new \Rbs\Price\Tax\TaxApplication($totalTax);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTotalTaxes()
	{
		return $this->totalTaxes;
	}

	/**
	 * @param \Rbs\Commerce\Process\BaseCreditNote[]|array[] $creditNotes
	 * @return $this
	 */
	public function setCreditNotes($creditNotes)
	{
		$this->creditNotes = [];
		if (is_array($creditNotes))
		{
			foreach ($creditNotes as $creditNote)
			{
				$this->creditNotes[] = new \Rbs\Commerce\Process\BaseCreditNote($creditNote);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\BaseCreditNote[]
	 */
	public function getCreditNotes()
	{
		return $this->creditNotes;
	}

	/**
	 * @param \Rbs\Order\Presentation\TransactionPresentation[] $transactions
	 * @return $this
	 */
	public function setTransactions($transactions)
	{
		$this->transactions = $transactions;
		return $this;
	}

	/**
	 * @return \Rbs\Order\Presentation\TransactionPresentation[]
	 */
	public function getTransactions()
	{
		return $this->transactions;
	}

	/**
	 * @param \Rbs\Order\Presentation\ShipmentPresentation[] $shipments
	 * @return $this
	 */
	public function setShipments($shipments)
	{
		$this->shipments = $shipments;
		return $this;
	}

	/**
	 * @return \Rbs\Order\Presentation\ShipmentPresentation[]
	 */
	public function getShipments()
	{
		return $this->shipments;
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context)
	{
		$this->context = $context;
		return $this;
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Events;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Documents\Order
 */
class Order extends \Compilation\Rbs\Order\Documents\Order
{
	const PROCESSING_STATUS_EDITION = 'edition';
	const PROCESSING_STATUS_PROCESSING = 'processing';
	const PROCESSING_STATUS_FINALIZED = 'finalized';
	const PROCESSING_STATUS_CANCELED = 'canceled';

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $content;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var \Rbs\Price\Tax\BaseTax[]
	 */
	protected $taxes;

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $lines;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $linesTaxes;

	/**
	 * @var \Rbs\Geo\Address\BaseAddress
	 */
	protected $address = false;

	/**
	 * @var \Rbs\Commerce\Process\BaseShippingMode[]
	 */
	protected $shippingModes;

	/**
	 * @var \Rbs\Commerce\Process\BaseCoupon[]
	 */
	protected $coupons;

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $fees;

	/**
	 * @var \Rbs\Commerce\Process\BaseDiscount[]
	 */
	protected $discounts;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $totalTaxes;

	/**
	 * @var \Rbs\Commerce\Process\BaseCreditNote[]
	 */
	protected $creditNotes;

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	protected  function getContent()
	{
		if ($this->content === null)
		{
			$v = $this->getContentData();
			$this->content = new \Zend\Stdlib\Parameters(is_array($v) ? $v : null);
		}
		return $this->content;
	}

	/**
	 * @return boolean
	 */
	protected  function hasLoadedContent()
	{
		return ($this->content !== null);
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
		$eventManager->attach(array(DocumentEvent::EVENT_UPDATED), array($this, 'onDefaultUpdated'), 5);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalize'], 5);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalizeModifiers'], 4);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalizeShippingModes'], 3);
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return 'Order:' . $this->getId();
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context = null)
	{
		$this->context = new \Zend\Stdlib\Parameters();
		if (is_array($context))
		{
			$this->context->fromArray($context);
		}
		elseif ($context instanceof \Traversable)
		{
			foreach ($context as $n => $v)
			{
				$this->context->set($n, $v);
			}
		}
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$this->setContext($this->getContent()->get('context'));
		}
		return $this->context;
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->getContext()->set('taxZone', $zone);
		return $this;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getZone()
	{
		return $this->getContext()->get('taxZone', null);
	}

	/**
	 * @param $taxes
	 * @return $this
	 */
	public function setTaxes($taxes = null)
	{
		$this->taxes = [];
		if (is_array($taxes))
		{
			foreach ($taxes as $tax)
			{
				if ($tax instanceof \Rbs\Price\Tax\BaseTax) {
					$this->taxes[] = $tax;
				}
				elseif ($tax instanceof \Rbs\Price\Tax\TaxInterface || is_array($tax)) {
					$this->taxes[] = new \Rbs\Price\Tax\BaseTax($tax);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return \Rbs\Price\Tax\BaseTax[]
	 */
	public function getTaxes()
	{
		if ($this->taxes === null)
		{
			$this->setTaxes($this->getContent()->get('taxes'));
		}
		return $this->taxes;
	}

	/**
	 * @api
	 * @return boolean|null
	 */
	public function getPricesValueWithTax()
	{
		return $this->getContext()->get('pricesValueWithTax', null);
	}

	/**
	 * @param boolean $pricesValueWithTax
	 * @return $this|\Zend\Stdlib\Parameters
	 */
	public function setPricesValueWithTax($pricesValueWithTax)
	{
		$this->getContext()->set('pricesValueWithTax', $pricesValueWithTax);
		return $this;
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 * @return $this
	 */
	public function setLines($lines = null)
	{
		$this->lines = [];
		if (is_array($lines))
		{
			foreach ($lines as $line)
			{
				if ($line instanceof \Rbs\Order\OrderLine)
				{
					$this->lines[] = $line;
				}
				elseif (is_array($line) || $line instanceof \Rbs\Commerce\Interfaces\LineInterface)
				{
					$this->lines[] = new \Rbs\Order\OrderLine($line);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return  \Rbs\Order\OrderLine[]
	 */
	public function getLines()
	{
		if ($this->lines === null)
		{
			$this->setLines($this->getContent()->get('lines'));
		}
		return $this->lines;
	}

	/**
	 * @param \Rbs\Order\OrderLine|array $line
	 */
	public function appendLine($line)
	{
		// Unserialize lines.
		$this->getLines();
		if ($line instanceof \Rbs\Order\OrderLine)
		{
			$this->lines[] = $line;
		}
		elseif (is_array($line))
		{
			$this->lines[] = new \Rbs\Order\OrderLine($line);
		}
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 */
	protected function updateLinesIndex(array $lines)
	{
		foreach ($lines as $index => $line)
		{
			$line->setIndex($index);
		}
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		$this->address = null;
		if ($address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$this->address = $address;
		}
		elseif (is_array($address) || $address instanceof \Rbs\Geo\Address\AddressInterface)
		{
			$this->address = new \Rbs\Geo\Address\BaseAddress($address);
		}
		return $this;
	}

	/**
	 * @api
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	public function getAddress()
	{
		if ($this->address === false)
		{
			$this->setAddress($this->getContent()->get('address'));
		}
		return $this->address;
	}

	/**
	 * @param array|\Rbs\Commerce\Process\ShippingModeInterface[] $shippingModes
	 * @return $this
	 */
	public function setShippingModes($shippingModes = null)
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
	 * @api
	 * @return \Rbs\Commerce\Process\BaseShippingMode[]
	 */
	public function getShippingModes()
	{
		if ($this->shippingModes === null)
		{
			$this->setShippingModes($this->getContent()->get('shippingModes'));
		}
		return $this->shippingModes;
	}

	/**
	 * @param array|\Rbs\Commerce\Interfaces\LineInterface[] $fees
	 * @return $this
	 */
	public function setFees($fees = null)
	{
		$this->fees = [];
		if (is_array($fees))
		{
			foreach ($fees as $fee)
			{
				if ($fee instanceof \Rbs\Order\OrderLine)
				{
					$this->fees[] = $fee;
				}
				elseif (is_array($fee) || $fee instanceof \Rbs\Commerce\Interfaces\LineInterface)
				{
					$this->fees[] = new \Rbs\Order\OrderLine($fee);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return  \Rbs\Order\OrderLine[]
	 */
	public function getFees()
	{
		if ($this->fees === null)
		{
			$this->setFees($this->getContent()->get('fees'));
		}
		return $this->fees;
	}

	/**
	 * @param array|\Rbs\Commerce\Process\DiscountInterface[] $discounts
	 * @return $this
	 */
	public function setDiscounts($discounts = null)
	{
		$this->discounts = [];
		if (is_array($discounts))
		{
			foreach ($discounts as $discount)
			{
				if ($discount instanceof \Rbs\Commerce\Process\BaseDiscount)
				{
					$this->discounts[] = $discount;
				}
				elseif (is_array($discount) || $discount instanceof \Rbs\Commerce\Process\DiscountInterface)
				{
					$this->discounts[] = new \Rbs\Commerce\Process\BaseDiscount($discount);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return \Rbs\Commerce\Process\BaseDiscount[]
	 */
	public function getDiscounts()
	{
		if ($this->discounts === null)
		{
			$this->setDiscounts($this->getContent()->get('discounts'));
		}
		return $this->discounts;
	}

	/**
	 * @param array|\Rbs\Commerce\Process\CouponInterface[] $coupons
	 * @return $this
	 */
	public function setCoupons($coupons = null)
	{
		$this->coupons = [];
		if (is_array($coupons))
		{
			foreach ($coupons as $coupon)
			{
				if ($coupon instanceof \Rbs\Commerce\Process\BaseCoupon)
				{
					$this->coupons[] = $coupon;
				}
				elseif (is_array($coupon) || $coupon instanceof \Rbs\Commerce\Process\CouponInterface)
				{
					$this->coupons[] = new \Rbs\Commerce\Process\BaseCoupon($coupon);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return \Rbs\Commerce\Process\BaseCoupon[]
	 */
	public function getCoupons()
	{
		if ($this->coupons === null)
		{
			$this->setCoupons($this->getContent()->get('coupons'));
		}
		return $this->coupons;
	}


	/**
	 * @param array|\Rbs\Commerce\Process\CreditNoteInterface[] $creditNotes
	 * @return $this
	 */
	public function setCreditNotes($creditNotes = null)
	{
		$this->creditNotes = [];
		if (is_array($creditNotes))
		{
			foreach ($creditNotes as $creditNote)
			{
				if ($creditNote instanceof \Rbs\Commerce\Process\BaseCreditNote)
				{
					$this->creditNotes[] = $creditNote;
				}
				elseif (is_array($creditNote) || $creditNote instanceof \Rbs\Commerce\Process\CreditNoteInterface)
				{
					$this->creditNotes[] = new \Rbs\Commerce\Process\BaseCreditNote($creditNote);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @return \Rbs\Commerce\Process\BaseCreditNote[]
	 */
	public function getCreditNotes()
	{
		if ($this->creditNotes === null)
		{
			$this->setCreditNotes($this->getContent()->get('creditNotes'));
		}
		return $this->creditNotes;
	}
	/**
	 * @param Events\Event $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		if ($this->isNew())
		{
			if (!$this->getCurrencyCode())
			{
				$billingArea = $this->getBillingAreaIdInstance();
				if ($billingArea) {
					$this->setCurrencyCode($billingArea->getCurrencyCode());
				}
			}
			if ($this->getPricesValueWithTax() === null) {
				$webStore =  $this->getWebStoreIdInstance();
				if ($webStore) {
					$this->setPricesValueWithTax($webStore->getPricesValueWithTax());
				}
			}
		}

		if ($this->address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$this->address->setLines($genericServices->getGeoManager()->getFormattedAddress($this->address));
			}
		}

		if ($this->isNew() || $this->isPropertyModified('billingAreaId'))
		{
			$ba = $this->getBillingAreaIdInstance();
			if ($ba instanceof \Rbs\Price\Documents\BillingArea)
			{
				$this->setCurrencyCode($ba->getCurrencyCode());
				$this->setTaxes($ba->getTaxes()->toArray());
			}
		}

		if ($this->getProcessingStatus() == 'edition')
		{
			if (is_array($this->lines) || is_array($this->coupons) ||
				is_array($this->fees) || is_array($this->discounts) || is_array($this->creditNotes))
			{
				$this->normalize();
			}
		}
		$this->setWrappedFields();
	}

	protected function setWrappedFields()
	{
		if ($this->context instanceof \Zend\Stdlib\Parameters)
		{
			$this->getContent()->set('context', $this->context->toArray());
			$this->context = null;
		}

		if (is_array($this->taxes))
		{
			$this->getContent()->set('taxes', array_map(function(\Rbs\Price\Tax\BaseTax $value)
			{
				return $value->toArray();
			}, $this->taxes));
			$this->taxes = null;
		}

		if (is_array($this->lines))
		{
			$this->updateLinesIndex($this->lines);
			$this->getContent()->set('lines', array_map(function(\Rbs\Order\OrderLine $value)
			{
				return $value->toArray();
			}, $this->lines));
			$this->lines = null;
		}

		if (is_array($this->linesTaxes))
		{
			$this->getContent()->set('linesTaxes', array_map(function(\Rbs\Price\Tax\TaxApplication $value)
			{
				return $value->toArray();
			}, $this->linesTaxes));
			$this->linesTaxes = null;
		}

		if ($this->address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$this->getContent()->set('address', $this->address->toArray());
			$this->address = false;
		}
		elseif ($this->address === null)
		{
			$this->getContent()->set('address', null);
			$this->address = false;
		}

		if (is_array($this->shippingModes))
		{
			$this->getContent()->set('shippingModes', array_map(function(\Rbs\Commerce\Process\BaseShippingMode $value)
			{
				return $value->toArray();
			}, $this->shippingModes));
			$this->shippingModes = null;
		}

		if (is_array($this->fees))
		{
			$this->updateLinesIndex($this->fees);
			$this->getContent()->set('fees', array_map(function(\Rbs\Order\OrderLine $value)
			{
				return $value->toArray();
			}, $this->fees));
			$this->fees = null;
		}

		if (is_array($this->coupons))
		{
			$this->getContent()->set('coupons', array_map(function(\Rbs\Commerce\Process\BaseCoupon $value)
			{
				return $value->toArray();
			}, $this->coupons));
			$this->coupons = null;
		}

		if (is_array($this->discounts))
		{
			$this->getContent()->set('discounts', array_map(function(\Rbs\Commerce\Process\BaseDiscount $value)
			{
				return $value->toArray();
			}, $this->discounts));
			$this->discounts = null;
		}

		if (is_array($this->totalTaxes))
		{
			$this->getContent()->set('totalTaxes', array_map(function(\Rbs\Price\Tax\TaxApplication $value)
			{
				return $value->toArray();
			}, $this->totalTaxes));
			$this->totalTaxes = null;
		}

		if (is_array($this->creditNotes))
		{
			$this->getContent()->set('creditNotes', array_map(function(\Rbs\Commerce\Process\BaseCreditNote $value)
			{
				return $value->toArray();
			}, $this->creditNotes));
			$this->creditNotes = null;
		}

		if ($this->content !== null) {
			$this->setContentData($this->content->toArray());
			$this->content = null;
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $order Order */
		$order = $event->getDocument();
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof \Change\Http\Rest\Result\Link)
			{
				$baseUrl = $selfLink->getPathInfo();
				$documentResult->addLink(new \Change\Http\Rest\Result\Link($um, $baseUrl . '/Shipments/', 'shipments'));
			}

			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			$currency = $order->getCurrencyCode();
			$nf->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
			$context = $order->getContext()->toArray();
			$context['decimals'] = $nf->getAttribute(\NumberFormatter::FRACTION_DIGITS);
			$context['formattedPaymentAmountWithTaxes'] = $nf->formatCurrency($order->getPaymentAmountWithTaxes(), $currency);
			$documentResult->setProperty('context', $context);

			$taxes = [];
			foreach ($order->getTaxes() as $tax)
			{
				$taxes[$tax->getCode()] = $tax->toArray();
				$taxes[$tax->getCode()]['label'] = $commerceServices->getPriceManager()->taxTitle($tax);
			}

			$documentResult->setProperty('taxes', count($taxes) ? $taxes : null);
			$documentResult->setProperty('lines', array_map(function(\Rbs\Order\OrderLine $line) {return $line->toArray();}, $order->getLines()));
			$documentResult->setProperty('linesAmount', $order->getLinesAmount());
			$documentResult->setProperty('linesTaxes', array_map(function(\Rbs\Price\Tax\TaxApplication $taxApp) {return $taxApp->toArray();}, $order->getLinesTaxes()));
			$documentResult->setProperty('linesAmountWithTaxes', $order->getLinesAmountWithTaxes());

			$callback = function(\Rbs\Order\OrderLine $fee) use ($nf, $currency)
			{
				$data = $fee->toArray();
				$data['options']['formattedAmount'] = $nf->formatCurrency($fee->getAmount(), $currency);
				$data['options']['formattedAmountWithTaxes'] = $nf->formatCurrency($fee->getAmountWithTaxes(), $currency);
				return $data;
			};
			$documentResult->setProperty('fees', array_map($callback, $order->getFees()));
			$documentResult->setProperty('coupons', array_map(function(\Rbs\Commerce\Process\BaseCoupon $coupon) {return $coupon->toArray();}, $order->getCoupons()));

			$callback = function(\Rbs\Commerce\Process\BaseDiscount $discount) use ($nf, $currency)
			{
				$data = $discount->toArray();
				$data['options']['formattedAmount'] = $nf->formatCurrency($discount->getAmount(), $currency);
				$data['options']['formattedAmountWithTaxes'] = $nf->formatCurrency($discount->getAmountWithTaxes(), $currency);
				return $data;
			};
			$documentResult->setProperty('discounts', array_map($callback, $order->getDiscounts()));

			$documentResult->setProperty('totalAmount', $order->getTotalAmount());
			$documentResult->setProperty('totalTaxes', array_map(function(\Rbs\Price\Tax\TaxApplication $taxApp) {return $taxApp->toArray();}, $order->getTotalTaxes()));
			$documentResult->setProperty('totalAmountWithTaxes', $order->getTotalAmountWithTaxes());

			$documentResult->setProperty('creditNotes', array_map(function(\Rbs\Commerce\Process\BaseCreditNote $creditNote) {return $creditNote->toArray();}, $order->getCreditNotes()));

			$address = $this->getAddress();
			$documentResult->setProperty('address', $address ? $address->toArray() : null);
			$documentResult->setProperty('shippingModes', array_map(function(\Rbs\Commerce\Process\BaseShippingMode $mode) {return $mode->toArray();}, $this->getShippingModes()));
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$linkResult = $restResult;
			if (!$linkResult->getProperty('code'))
			{
				$linkResult->setProperty('code', $linkResult->getProperty('label'));
			}

			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			$formattedAmount = $nf->formatCurrency($order->getPaymentAmountWithTaxes(), $order->getCurrencyCode());
			$restResult->setProperty('formattedPaymentAmountWithTaxes', $formattedAmount);
		}
	}

	protected $ignoredPropertiesForRestEvents = array('model', 'paymentAmountWithTaxes', 'currencyCode');

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch($name)
		{
			case 'context':
				$this->setContext($value);
				break;
			case 'lines':
				$this->setLines($value);
				break;
			case 'address':
				$this->setAddress($value);
				break;
			case 'shippingModes':
				$this->setShippingModes(is_array($value) ? $value : null);
				break;
			case 'fees':
				$this->setFees($value);
				break;
			case 'discounts':
				$this->setDiscounts($value);
				break;
			case 'coupons':
				$this->setCoupons($value);
				break;
			case 'creditNotes':
				$this->setCreditNotes($value);
				break;
			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function normalize()
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['order' => $this]);
		$this->getEventManager()->trigger('normalize', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalize(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if ($order instanceof Order)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$stockManager = $commerceServices->getStockManager();
			$priceManager = $commerceServices->getPriceManager();

			$webStore = $order->getWebStoreIdInstance();
			if ($webStore)
			{
				$order->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			foreach ($order->getLines() as $index => $line)
			{
				$line->setIndex($index);
				$this->refreshOrderLine($order, $line, $priceManager, $stockManager);
			}

			$this->refreshLinesAmount($order, $priceManager);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeModifiers(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if ($order instanceof Order)
		{
			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$stockManager = $commerceServices->getStockManager();
			$priceManager = $commerceServices->getPriceManager();

			/*
				$cart->removeAllFees();
				$cart->removeAllDiscounts();

				$processManager = $commerceServices->getProcessManager();
				$process = $processManager->getOrderProcessByCart($cart);
				if ($process)
				{
					$documents = $process->getAvailableModifiers();
					foreach ($documents as $document)
					{
						if ($document instanceof \Rbs\Commerce\Documents\Fee) {
							$modifier = $document->getValidModifier($cart);
							if ($modifier) {
								$modifier->apply();
							}
						}
						elseif ($document instanceof \Rbs\Discount\Documents\Discount) {
							$modifier = $document->getValidModifier($cart);
							if ($modifier) {
								$modifier->apply();
							}
						}
					}
				}
			*/

			// Add fees and discounts.
			$totalAmount = $order->getLinesAmount();
			$totalAmountWithTaxes = $order->getLinesAmountWithTaxes();
			$totalTaxes = $order->getLinesTaxes();
			foreach ($order->getFees() as $index => $fee)
			{
				$fee->setIndex($index);
				$this->refreshOrderLine($order, $fee, $priceManager, $stockManager);
				$this->refreshLineAmount($fee, $order, $priceManager);

				$totalAmount += $fee->getAmount();
				$totalAmountWithTaxes += $fee->getAmountWithTaxes();
				$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $fee->getTaxes());
			}

			foreach ($order->getDiscounts() as $discount)
			{
				// TODO: refresh discount taxes.

				$totalAmount += $discount->getAmount();
				$totalAmountWithTaxes += $discount->getAmountWithTaxes();
				$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $discount->getTaxes());
			}

			$order->setTotalAmount($totalAmount);
			$order->setTotalAmountWithTaxes($totalAmountWithTaxes);
			$order->setTotalTaxes($totalTaxes);

			//Add Credit notes
			$paymentAmountWithTaxes = $totalAmountWithTaxes;
			foreach ($order->getCreditNotes() as $creditNote)
			{
				$paymentAmountWithTaxes += $creditNote->getAmount();
			}

			$order->setPaymentAmountWithTaxes($paymentAmountWithTaxes);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeShippingModes(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if ($order instanceof Order)
		{
			/* @var $genericServices \Rbs\Generic\GenericServices */
			$genericServices = $event->getServices('genericServices');

			foreach ($order->getShippingModes() as $shippingMode)
			{
				$address = $shippingMode->getAddress();
				if ($address)
				{
					$address->setLines($genericServices->getGeoManager()->getFormattedAddress($address));
				}
			}
		}
	}

	/**
	 * @param Order $order
	 * @param \Rbs\Order\OrderLine $line
	 * @param \Rbs\Price\PriceManager $priceManager
	 * @param \Rbs\Stock\StockManager $stockManager
	 */
	public function refreshOrderLine(Order $order, \Rbs\Order\OrderLine $line, $priceManager, $stockManager)
	{
		$webStore = $order->getWebStoreIdInstance();
		$billingArea = $order->getBillingAreaIdInstance();
		$pricesValueWithTax = $order->getPricesValueWithTax();
		foreach ($line->getItems() as $item)
		{
			if (!$item->getOptions()->get('lockedPrice', false))
			{
				$sku = $stockManager->getSkuByCode($item->getCodeSKU());
				if ($webStore && $billingArea && $sku)
				{
					$price = $priceManager->getPriceBySku($sku,
						['webStore' => $webStore, 'billingArea' => $billingArea, 'order' => $order, 'orderLine' => $line]);
					$item->setPrice($price);
				}
				else
				{
					$item->setPrice(null);
				}
			}
			$price = $item->getPrice();
			if ($price)
			{
				$price->setWithTax($pricesValueWithTax);
			}
		}
	}

	/**
	 * @param Order $order
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	protected function refreshLinesAmount(Order $order, $priceManager )
	{
		/* @var $linesTaxes \Rbs\Price\Tax\TaxApplication[] */
		$linesTaxes = [];
		$linesAmount = 0.0;
		$linesAmountWithTaxes = 0.0;

		foreach ($order->getLines() as $line)
		{
			$this->refreshLineAmount($line, $order, $priceManager);
			$linesAmount += $line->getAmount();
			$linesAmountWithTaxes += $line->getAmountWithTaxes();
			$linesTaxes = $priceManager->addTaxesApplication($linesTaxes, $line->getTaxes());
		}

		$order->setLinesTaxes($linesTaxes);
		$order->setLinesAmount($linesAmount);
		$order->setLinesAmountWithTaxes($linesAmountWithTaxes);
	}

	/**
	 * @param \Rbs\Order\OrderLine $line
	 * @param \Rbs\Order\Documents\Order $order
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	protected function refreshLineAmount($line, Order $order, $priceManager)
	{
		$currencyCode = $this->getCurrencyCode();
		$zone = $order->getZone();
		$billingArea = $order->getBillingAreaIdInstance();
		$taxes = ($billingArea &&  $zone && $currencyCode) ? $billingArea->getTaxes()->toArray() : [];

		$lineTaxes = [];
		$amount = null;
		$amountWithTaxes = null;
		$lineQuantity = $line->getQuantity();
		if ($lineQuantity)
		{
			foreach ($line->getItems() as $item)
			{
				$price = $item->getPrice();
				if ($price && (($value = $price->getValue()) !== null))
				{
					$lineItemValue = $value * $lineQuantity;
					if ($taxes !== null)
					{
						$taxArray = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $lineQuantity);
						if (count($taxArray))
						{
							$lineTaxes = $priceManager->addTaxesApplication($lineTaxes, $taxArray);
						}

						if ($price->isWithTax())
						{
							$amountWithTaxes += $lineItemValue;
							$amount += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);
						}
						else
						{
							$amount += $lineItemValue;
							$amountWithTaxes = $priceManager->getValueWithTax($lineItemValue, $taxArray);
						}
					}
					else
					{
						$amountWithTaxes += $lineItemValue;
						$amount += $lineItemValue;
					}
				}
			}
		}
		$line->setTaxes($lineTaxes);
		$line->setAmountWithTaxes($amountWithTaxes);
		$line->setAmount($amount);
	}

	/**
	 * @param float|null $linesAmount
	 * @return $this
	 */
	public function setLinesAmount($linesAmount)
	{
		$this->getContent()->set('linesAmount', $linesAmount);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getLinesAmount()
	{
		return $this->getContent()->get('linesAmount');
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $linesTaxes
	 * @return $this
	 */
	public function setLinesTaxes($linesTaxes)
	{
		$this->linesTaxes = [];
		if (is_array($linesTaxes))
		{
			foreach ($linesTaxes as $tax)
			{
				if ($tax instanceof \Rbs\Price\Tax\TaxApplication)
				{
					$this->linesTaxes[] = $tax;
				}
				elseif (is_array($tax))
				{
					$this->linesTaxes[] = (new \Rbs\Price\Tax\TaxApplication($tax));
				}
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getLinesTaxes()
	{
		if ($this->linesTaxes === null)
		{
			$this->setLinesTaxes($this->getContent()->get('linesTaxes'));
		}
		return $this->linesTaxes;
	}


	/**
	 * @param float|null $linesAmountWithTaxes
	 * @return $this
	 */
	public function setLinesAmountWithTaxes($linesAmountWithTaxes)
	{
		$this->getContent()->set('linesAmountWithTaxes', $linesAmountWithTaxes);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getLinesAmountWithTaxes()
	{
		return $this->getContent()->get('linesAmountWithTaxes');
	}


	/**
	 * @param float|null $totalAmount
	 * @return $this
	 */
	public function setTotalAmount($totalAmount)
	{
		$this->getContent()->set('totalAmount', $totalAmount);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getTotalAmount()
	{
		return $this->getContent()->get('totalAmount');
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $totalTaxes
	 * @return $this
	 */
	public function setTotalTaxes($totalTaxes)
	{
		$this->totalTaxes = [];
		if (is_array($totalTaxes))
		{
			foreach ($totalTaxes as $tax)
			{
				if ($tax instanceof \Rbs\Price\Tax\TaxApplication)
				{
					$this->totalTaxes[] = $tax;
				}
				elseif (is_array($tax))
				{
					$this->totalTaxes[] = (new \Rbs\Price\Tax\TaxApplication($tax));
				}
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTotalTaxes()
	{
		if ($this->totalTaxes === null)
		{
			$this->setTotalTaxes($this->getContent()->get('totalTaxes'));
		}
		return $this->totalTaxes;
	}

	/**
	 * @param float|null $totalAmountWithTaxes
	 * @return $this
	 */
	public function setTotalAmountWithTaxes($totalAmountWithTaxes)
	{
		$this->getContent()->set('totalAmountWithTaxes', $totalAmountWithTaxes);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getTotalAmountWithTaxes()
	{
		return $this->getContent()->get('totalAmountWithTaxes');
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdated($event)
	{
		$order = $event->getDocument();
		/* @var $order \Rbs\Order\Documents\Order */
		$orderProcessingStatus = $order->getProcessingStatus();
		if ($orderProcessingStatus === self::PROCESSING_STATUS_FINALIZED || $orderProcessingStatus === self::PROCESSING_STATUS_CANCELED)
		{
			$jobManager = $event->getApplicationServices()->getJobManager();
			$jobManager->createNewJob('Rbs_Order_Order_Complete', ['orderId' => $order->getId()]);
		}
	}
}

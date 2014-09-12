<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\ProcessManager
 */
class ProcessManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ProcessManager';

	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @var \Rbs\Commerce\Filters\Filters
	 */
	protected $filters;

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @return $this
	 */
	public function setCartManager(\Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	protected function getCartManager()
	{
		return $this->cartManager;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/ProcessManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getNewTransaction', [$this, 'onDefaultGetNewTransaction'], 5);
		$eventManager->attach('createInvoiceFromOrder', [$this, 'onDefaultCreateInvoiceFromOrder'], 5);
		$eventManager->attach('createOrderFromCart', [$this, 'onDefaultCreateOrderFromCart'], 5);
		$eventManager->attach('createOrderFromCart', [$this, 'sendOrderConfirmationMail'], 1);

		$eventManager->attach('getOrderProcessByCart', [$this, 'onDefaultGetOrderProcessByCart'], 5);
		$eventManager->attach('getCompatibleShippingModes', [$this, 'onDefaultGetCompatibleShippingModes'], 5);
		$eventManager->attach('getCompatiblePaymentConnectors', [$this, 'onDefaultGetCompatiblePaymentConnectors'], 5);
		$eventManager->attach('getShippingZones', [$this, 'onDefaultGetShippingZones'], 5);
		$eventManager->attach('getShippingFee', [$this, 'onDefaultGetShippingFee'], 5);
		$eventManager->attach('getShippingFeesEvaluation', [$this, 'onDefaultGetShippingFeesEvaluation'], 5);
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getFiltersDefinition($options = [])
	{
		if ($this->filters === null)
		{
			$this->filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
		}
		return $this->filters->getDefinitions($options);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $filter
	 * @param array $options
	 * @return boolean
	 */
	public function isValidFilter(\Rbs\Commerce\Cart\Cart $cart, $filter, $options = [])
	{
		if ($this->filters === null)
		{
			$this->filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
		}
		return $this->filters->isValid($cart, $filter, $options);
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Commerce\Documents\Process|null
	 */
	public function getOrderProcessByCart($cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['cart' => $cart]);
		$this->getEventManager()->trigger('getOrderProcessByCart', $this, $args);
		if (isset($args['process']) && $args['process'] instanceof \Rbs\Commerce\Documents\Process)
		{
			return $args['process'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetOrderProcessByCart(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart) {
			$webstore = $dm->getDocumentInstance($cart->getWebStoreId());
			if ($webstore instanceof \Rbs\Store\Documents\WebStore) {
				$process = $webstore->getOrderProcess();
				if ($process && $process->activated()) {
					$event->setParam('process', $process);
				}
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $options
	 * @return \Rbs\Shipping\Documents\Mode[]
	 */
	public function getCompatibleShippingModes($orderProcess, $cart, array $options = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart, 'options' => $options]);
		$this->getEventManager()->trigger('getCompatibleShippingModes', $this, $args);
		if (isset($args['shippingModes']) && is_array($args['shippingModes']))
		{
			return $args['shippingModes'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCompatibleShippingModes(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		$options = $event->getParam('options');
		$needAddress = isset($options['needAddress']) ? $options['needAddress'] : null;
		$deliveryIndex = isset($options['deliveryIndex']) ? $options['deliveryIndex'] : null;

		/* @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		$geoManager = ($genericServices instanceof \Rbs\Generic\GenericServices) ? $genericServices->getGeoManager() : null;

		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$shippingModes = [];
			foreach ($orderProcess->getShippingModes() as $shippingMode)
			{
				if ($needAddress !== null && $shippingMode->getHasAddress() !== $needAddress )
				{
					continue;
				}
				if (!$shippingMode->isCompatibleWith($cart))
				{
					continue;
				}

				if ($shippingMode->getDeliveryZonesCount())
				{
					$address = $cart->getAddress();
					$cartShippingModes = $cart->getShippingModes();
					if ($deliveryIndex !== null && isset($cartShippingModes[$deliveryIndex]))
					{
						$cartShippingMode = $cartShippingModes[$deliveryIndex];
						$address = $cartShippingMode->getAddressReference();
					}
					if ($address instanceof \Rbs\Geo\Address\AddressInterface && $geoManager)
					{
						foreach ($shippingMode->getDeliveryZones() as $zone)
						{
							if ($geoManager->isValidAddressForZone($address, $zone))
							{
								$shippingModes[] = $shippingMode;
								break;
							}
						}
					}
				}
				else
				{
					$shippingModes[] = $shippingMode;
				}
			}
			$event->setParam('shippingModes', $shippingModes);
		}
	}


	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Payment\Documents\Connector[]
	 */
	public function getCompatiblePaymentConnectors($orderProcess, $cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart]);
		$this->getEventManager()->trigger('getCompatiblePaymentConnectors', $this, $args);
		if (isset($args['paymentConnectors']) && is_array($args['paymentConnectors']))
		{
			return $args['paymentConnectors'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCompatiblePaymentConnectors(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$paymentConnectors = [];
			foreach ($orderProcess->getPaymentConnectors() as $paymentConnector)
			{
				if ($paymentConnector->isCompatibleWith($cart))
				{
					$paymentConnectors[] = $paymentConnector;
				}
			}
			$event->setParam('paymentConnectors', $paymentConnectors);
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Geo\Documents\Zone[]|null
	 */
	public function getShippingZones($orderProcess, $cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart]);
		$this->getEventManager()->trigger('getShippingZones', $this, $args);
		if (isset($args['zones']))
		{
			return $args['zones'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetShippingZones(\Change\Events\Event $event)
	{
		$orderProcess = $event->getParam('orderProcess');
		if ($orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$zones = [];
			foreach ($orderProcess->getShippingModes() as $shippingMode)
			{
				if ($shippingMode->activated())
				{
					foreach ($shippingMode->getDeliveryZones() as $zone)
					{
						$zones[$zone->getId()] = $zone;
					}
				}
			}
			if (count($zones))
			{
				$event->setParam('zones', array_values($zones));
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Shipping\Documents\Mode $shippingMode
	 * @return \Rbs\Commerce\Documents\Fee|null
	 */
	public function getShippingFee($orderProcess, $cart, $shippingMode)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart, 'shippingMode' => $shippingMode]);
		$this->getEventManager()->trigger('getShippingFee', $this, $args);
		if (isset($args['fee']) && $args['fee'] instanceof \Rbs\Commerce\Documents\Fee)
		{
			return $args['fee'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetShippingFee(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		$shippingMode = $event->getParam('shippingMode');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart &&
			$orderProcess instanceof \Rbs\Commerce\Documents\Process &&
			$shippingMode instanceof \Rbs\Shipping\Documents\Mode)
		{
			$q = $dm->getNewQuery('Rbs_Commerce_Fee');
			$q->andPredicates($q->activated(), $q->eq('orderProcess', $orderProcess), $q->eq('shippingMode', $shippingMode));
			/** @var $fee \Rbs\Commerce\Documents\Fee */
			foreach ($q->getDocuments() as $fee)
			{
				if ($fee->getValidModifier($cart, ['shippingMode' => $shippingMode]))
				{
					$event->setParam('fee', $fee);
					return;
				}
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return array
	 */
	public function getShippingFeesEvaluation($orderProcess, $cart, $website)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart, 'website' => $website]);
		$this->getEventManager()->trigger('getShippingFeesEvaluation', $this, $args);
		if (isset($args['feesEvaluation']) && is_array($args['feesEvaluation']))
		{
			return $args['feesEvaluation'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetShippingFeesEvaluation(\Change\Events\Event $event)
	{

		$feesEvaluation = ['countries' => [], 'shippingModes' => []];
		$globalCountries = [];
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		/** @var $website \Change\Presentation\Interfaces\Website */
		$website = $event->getParam('website');
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices && $cart instanceof \Rbs\Commerce\Cart\Cart &&
			$orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$priceManager = $commerceServices->getPriceManager();
			$currencyCode = $cart->getCurrencyCode();
			$zone = $cart->getZone();
			$taxes = $cart->getTaxes();
			if (!$currencyCode || !$zone || count($taxes) == 0)
			{
				$taxes = null;
			}
			if ($website)
			{
				$richTextContext = [
					'website' => $website,
					'currentURI' => $website->getUrlManager($i18nManager->getLCID())->getBaseUri()
				];
			}
			else
			{
				$richTextContext = null;
			}

			$webStore = $documentManager->getDocumentInstance($cart->getWebStoreId());
			$billingArea = $cart->getBillingArea();
			$priceOptions = ['webStore' => $webStore, 'billingArea' => $billingArea, 'cart' => $cart];
			foreach ($orderProcess->getShippingModes() as $shippingMode)
			{
				if (!$shippingMode->activated())
				{
					continue;
				}

				$countries = [];
				foreach ($shippingMode->getDeliveryZones() as $deliveryZone)
				{
					$country = $deliveryZone->getCountry();
					if ($country && $country->getAddressFields())
					{
						$countries[$country->getCode()] = $i18nManager->trans($country->getI18nTitleKey(), ['ucf']);
					}
				}

				if (count($countries))
				{
					$globalCountries = array_merge($globalCountries, $countries);
					$shippingModeEntry = ['id' => $shippingMode->getId(), 'countries' => $countries,
						'sku' => null, 'amount' => null, 'amountWithTax' => null,
						'title' => $shippingMode->getCurrentLocalization()->getTitle(),
						'description' => null, 'visualUrl' => null];

					if ($richTextContext)
					{
						$shippingModeEntry['description'] = $event->getApplicationServices()->getRichTextManager()->render($shippingMode->getCurrentLocalization()->getDescription(), 'Website', $richTextContext);
					}

					$fee = $this->getShippingFee($orderProcess, $cart, $shippingMode);
					if ($fee && (($sku = $fee->getSku()) != null))
					{
						$shippingModeEntry['sku'] = $sku->getCode();
						$price = $priceManager->getPriceBySku($sku, $priceOptions);
						if ($price && $price->getValue())
						{
							$shippingTaxes = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode);
							if ($price->isWithTax())
							{
								$amout = $priceManager->getValueWithoutTax($price->getValue(), $shippingTaxes);
								$amoutWithTax = $price->getValue();
							}
							else
							{
								$amout = $price->getValue();
								$amoutWithTax = $priceManager->getValueWithTax($price->getValue(), $shippingTaxes);
							}

							$shippingModeEntry['amountWithTax'] = $amoutWithTax;
							$shippingModeEntry['amount'] = $amout;
							$shippingModeEntry['formattedAmountWithTax'] = $priceManager->formatValue($amoutWithTax, $currencyCode);
							$shippingModeEntry['formattedAmount'] = $priceManager->formatValue($amout, $currencyCode);

						}
					}

					$visual = $shippingMode->getVisual();
					if ($visual)
					{
						$shippingModeEntry['visualUrl'] = $visual->getPublicURL(160, 90); // TODO: get size as a parameter?
					}

					$feesEvaluation['shippingModes'][] = $shippingModeEntry;
				}
			}
		}

		if (count($globalCountries))
		{
			$feesEvaluation['countriesCount'] = count($globalCountries);

			foreach($globalCountries as $key => $value)
			{
				$feesEvaluation['countries'][] = ['code' => $key, 'title' => $value];
			}
			$event->setParam('feesEvaluation', $feesEvaluation);
		}
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @param float $amount
	 * @param string $currencyCode
	 * @param string $email
	 * @param integer $userId
	 * @param integer $ownerId
	 * @param array $contextData
	 * @throws \Exception
	 * @return \Rbs\Payment\Documents\Transaction|null
	 */
	public function getNewTransaction($targetIdentifier, $amount, $currencyCode, $email, $userId, $ownerId, $contextData = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'targetIdentifier' => $targetIdentifier,
			'amount' => $amount,
			'currencyCode' => $currencyCode,
			'email' => $email,
			'userId' => $userId,
			'ownerId' => $ownerId,
			'contextData' => $contextData
		));
		$this->getEventManager()->trigger('getNewTransaction', $this, $args);
		if (isset($args['transaction']) && $args['transaction'] instanceof \Rbs\Payment\Documents\Transaction)
		{
			return $args['transaction'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetNewTransaction(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			/** @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $dm->getNewDocumentInstanceByModelName('Rbs_Payment_Transaction');
			$transaction->setTargetIdentifier($event->getParam('targetIdentifier'));
			$transaction->setAmount($event->getParam('amount'));
			$transaction->setCurrencyCode($event->getParam('currencyCode'));
			$transaction->setEmail($event->getParam('email'));
			$transaction->setAuthorId($event->getParam('userId'));
			$transaction->setOwnerId($event->getParam('ownerId') ? $event->getParam('ownerId') : $event->getParam('userId'));
			$transaction->setContextData($event->getParam('contextData'));
			$transaction->save();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('transaction', $transaction);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Order\Documents\Order|null
	 */
	public function createOrderFromCart($cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart));
		$this->getEventManager()->trigger('createOrderFromCart', $this, $args);
		if (isset($args['order']) && $args['order'] instanceof \Rbs\Order\Documents\Order)
		{
			return $args['order'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultCreateOrderFromCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$documentManager = $event->getApplicationServices()->getDocumentManager();

				/* @var $order \Rbs\Order\Documents\Order */
				$order = $documentManager->getNewDocumentInstanceByModelName('Rbs_Order_Order');
				$order->setContext($cart->getContext()->toArray());
				$order->getContext()->set('cartIdentifier', $cart->getIdentifier());
				$order->getContext()->set('transactionId', $cart->getTransactionId());
				$order->setCreationDate($cart->lastUpdate());
				$order->setAuthorId($cart->getUserId());
				$order->setEmail($cart->getEmail());

				$order->setOwnerId($cart->getOwnerId() ? $cart->getOwnerId() : $cart->getUserId());
				$order->setWebStoreId($cart->getWebStoreId());
				$order->setBillingAreaId($cart->getBillingArea()->getId());
				$order->setCurrencyCode($cart->getCurrencyCode());
				$order->setZone($cart->getZone());
				$order->setTaxes($cart->getTaxes());

				foreach ($cart->getLines() as $line)
				{
					$order->appendLine($line->toArray());
				}
				$order->setLinesAmount($cart->getLinesAmount());
				$order->setLinesTaxes($cart->getLinesTaxes());
				$order->setLinesAmountWithTaxes($cart->getLinesAmountWithTaxes());

				$order->setAddress($cart->getAddress()->toArray());
				$order->setShippingModes($cart->getShippingModes());

				$order->setCoupons($cart->getCoupons());
				$order->setFees($cart->getFees());
				$order->setDiscounts($cart->getDiscounts());

				$order->setTotalAmount($cart->getTotalAmount());
				$order->setTotalTaxes($cart->getTotalTaxes());
				$order->setTotalAmountWithTaxes($cart->getTotalAmountWithTaxes());

				$order->setCreditNotes($cart->getCreditNotes());
				$order->setPaymentAmountWithTaxes($cart->getPaymentAmountWithTaxes());

				$order->setProcessingStatus(\Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING);
				$order->save();

				$orderIdentifier = $order->getIdentifier();
				if ($cart->getTransactionId())
				{
					$transaction = $documentManager->getDocumentInstance($cart->getTransactionId());
					if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
					{
						$transaction->setTargetIdentifier($orderIdentifier);
						$contextData = $transaction->getContextData();
						if (!is_array($contextData)) {
							$contextData = [];
						}
						$contextData['from'] = 'order';
						$transaction->setContextData($contextData);
						$transaction->update();
						$event->setParam('transaction', $transaction);

						$invoice = $this->createInvoiceFromOrder($order, $transaction);
						$event->setParam('invoice', $invoice);
					}
				}

				if (is_array($order->getCreditNotes()))
				{
					foreach($order->getCreditNotes() as $creditNoteData)
					{
						$document = $documentManager->getDocumentInstance($creditNoteData->getId());
						if ($document instanceof \Rbs\Order\Documents\CreditNote)
						{
							$document->renameTargetIdentifier($cart->getIdentifier(), $orderIdentifier);
							$document->save();
						}
					}
				}

				$commerceServices = $event->getServices('commerceServices');
				if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
				{
					$commerceServices->getStockManager()->confirmReservations($cart->getIdentifier(), $orderIdentifier);
				}

				//Delete obsolete cart
				$this->getCartManager()->deleteCart($cart, true);

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$event->setParam('order', $order);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function sendOrderConfirmationMail(\Change\Events\Event $event)
	{
		/** @var \Rbs\Order\Documents\Order $order */
		$order = $event->getParam('order', null);
		if ($order != null)
		{
			$jobManager = $event->getApplicationServices()->getJobManager();
			$argument = ['notificationName' => 'rbs_commerce_order_confirmation', 'targetId' => $order->getId()];
			$jobManager->createNewJob('Rbs_Notification_ProcessTransactionalNotification', $argument);
		}
	}

	/**
	 * @api
	 * @param \Rbs\Order\Documents\Order $order
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return \Rbs\Order\Documents\Invoice|null
	 */
	public function createInvoiceFromOrder($order, $transaction)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['order' => $order, 'transaction' => $transaction]);
		$this->getEventManager()->trigger('createInvoiceFromOrder', $this, $args);
		if (isset($args['invoice']) && $args['invoice'] instanceof \Rbs\Order\Documents\Invoice)
		{
			return $args['invoice'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultCreateInvoiceFromOrder(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		$transaction = $event->getParam('transaction');

		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$invoice = null;
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$documentManager = $event->getApplicationServices()->getDocumentManager();

				/* @var $invoice \Rbs\Order\Documents\Invoice */
				$invoice = $documentManager->getNewDocumentInstanceByModelName('Rbs_Order_Invoice');
				$invoice->setOrder($order);

				if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
				{
					$invoice->setTransaction($transaction);
					$invoice->setAmountWithTax($transaction->getAmount());
					$invoice->setCurrencyCode($transaction->getCurrencyCode());
				}
				else
				{
					$invoice->setAmountWithTax($order->getPaymentAmountWithTaxes());
					$invoice->setCurrencyCode($order->getCurrencyCode());
				}

				$invoice->setCode($this->getNewCode($invoice));
				$invoice->create();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$event->setParam('invoice', $invoice);
		}
	}

	/**
	 * By default accept: \Rbs\Order\Documents\Order, \Rbs\Order\Documents\Invoice,
	 *  \Rbs\Order\Documents\Shipment and \Rbs\Order\Documents\CreditNote
	 * @api
	 * @param mixed $document
	 * @return string|null
	 */
	public function getNewCode($document)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['document' => $document]);
		$this->getEventManager()->trigger('getNewCode', $this, $args);
		if (isset($args['newCode']))
		{
			return strval($args['newCode']);
		}
		return null;
	}
}
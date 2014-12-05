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
		$eventManager->attach('getShippingModeData', [$this, 'onDefaultGetShippingModeData'], 5);
		$eventManager->attach('getShippingModesDataByAddress', [$this, 'onDefaultGetShippingModesDataByAddress'], 5);
		$eventManager->attach('isValidAddressForShippingMode', [$this, 'onDefaultIsValidAddressForShippingMode'], 5);

		$eventManager->attach('getCompatiblePaymentConnectors', [$this, 'onDefaultGetCompatiblePaymentConnectors'], 5);
		$eventManager->attach('getShippingZones', [$this, 'onDefaultGetShippingZones'], 5);
		$eventManager->attach('getShippingFee', [$this, 'onDefaultGetShippingFee'], 5);
		$eventManager->attach('getShippingFeesEvaluation', [$this, 'onDefaultGetShippingFeesEvaluation'], 5);


		$eventManager->attach('getPaymentConnectorData', [$this, 'onDefaultGetPaymentConnectorData'], 5);

		$eventManager->attach('getProcessData', [$this, 'onDefaultGetProcessData'], 5);
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
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cart->getWebStoreId())
		{
			$webStore = $dm->getDocumentInstance($cart->getWebStoreId());
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$process = $webStore->getOrderProcess();
				if ($process && $process->activated())
				{
					$event->setParam('process', $process);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Shipping\Documents\Mode|integer $shippingMode
	 * @param \Rbs\Geo\Address\AddressInterface|array $address
	 * @param array $options
	 * @return boolean
	 */
	public function isValidAddressForShippingMode($shippingMode, $address, array $options = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['shippingMode' => $shippingMode, 'address' => $address, 'options' => $options]);
		$this->getEventManager()->trigger('isValidAddressForShippingMode', $this, $args);
		if (isset($args['isValid']))
		{
			return $args['isValid'] == true;
		}
		return false;
	}


	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidAddressForShippingMode(\Change\Events\Event $event)
	{
		if ($event->getParam('isValid') !== null)
		{
			return;
		}

		$shippingMode = $event->getParam('shippingMode');
		if (is_numeric($shippingMode))
		{
			$shippingMode = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shippingMode);
		}
		if (!$shippingMode instanceof \Rbs\Shipping\Documents\Mode)
		{
			return;
		}

		/** @var \Rbs\Geo\Address\AddressInterface $address */
		$address = $event->getParam('address');
		if (is_array($address))
		{
			$address = new \Rbs\Geo\Address\BaseAddress($address);
		}

		if (!$address instanceof \Rbs\Geo\Address\AddressInterface)
		{
			return;
		}

		if ($shippingMode->getDeliveryZonesCount())
		{
			/* @var $genericServices \Rbs\Generic\GenericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();
			foreach ($shippingMode->getDeliveryZones() as $zone)
			{
				if ($geoManager->isValidAddressForZone($address, $zone))
				{
					$event->setParam('isValid', true);
					return;
				}
			}
			$event->setParam('isValid', false);
		}
		else
		{
			$event->setParam('isValid', true);
		}
	}

	/**
	 * @api
	 *  Default context:
	 *  - data:
	 *     - cartId
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param array $context
	 * @return \Rbs\Shipping\Documents\Mode[]
	 */
	public function getCompatibleShippingModes($orderProcess, array $context)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'context' => $context]);
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
		if ($event->getParam('shippingModes') !== null)
		{
			return;
		}
		$context = $event->getParam('context');
		if (isset($context['data']['cartId']))
		{
			/** @var \Rbs\Commerce\CommerceServices $commerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($context['data']['cartId']);
		}
		else
		{
			$cart = null;
		}

		$orderProcess = $event->getParam('orderProcess');
		if ($orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$shippingModes = [];
			foreach ($orderProcess->getShippingModes() as $shippingMode)
			{
				if (!$shippingMode->activated() || ($cart && !$shippingMode->isCompatibleWith($cart)))
				{
					continue;
				}
				$shippingModes[] = $shippingMode;
			}
			$event->setParam('shippingModes', $shippingModes);
		}
	}

	/**
	 * @api
	 * Default context:
	 *  - *visualFormats, *website
	 * @param \Rbs\Payment\Documents\Connector|integer $paymentConnector
	 * @param array $context
	 * @return array
	 */
	public function getPaymentConnectorData($paymentConnector, array $context)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['paymentConnector' => $paymentConnector, 'context' => $context]);
		$em->trigger('getPaymentConnectorData', $this, $args);
		if (isset($args['paymentConnectorData']) && is_array($args['paymentConnectorData']))
		{
			return $args['paymentConnectorData'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetPaymentConnectorData(\Change\Events\Event $event)
	{
		if ($event->getParam('paymentConnectorData') !== null)
		{
			return;
		}

		$paymentConnector = $event->getParam('paymentConnector');
		if (is_numeric($paymentConnector))
		{
			$paymentConnector = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($paymentConnector);
		}
		if (!$paymentConnector instanceof \Rbs\Payment\Documents\Connector)
		{
			return;
		}

		$context = $event->getParam('context');
		if (!is_array($context))
		{
			$context = [];
		}

		// Set default context values.
		$context += ['visualFormats' => [], 'website' => null, 'data' => [], 'detailed' => false, 'dataSetNames' => []];

		$applicationServices = $event->getApplicationServices();

		$paymentConnectorData = ['common' => [
			'id' => $paymentConnector->getId(),
			'title' => $paymentConnector->getCurrentLocalization()->getTitle(),
			'category' => 'default',
		]];

		if ($context['detailed'])
		{
			$visualFormats = $context['visualFormats'];
			$website = $context['website'];
			$richTextContext = array('website' => $website);
			$richTextManager = $applicationServices->getRichTextManager();

			$paymentConnectorData['presentation'] = [
				'description' => $richTextManager->render($paymentConnector->getCurrentLocalization()
					->getDescription(), 'Website', $richTextContext)
			];
			if ($paymentConnector instanceof \Rbs\Payment\Documents\DeferredConnector)
			{
				$paymentConnectorData['presentation']['instructions'] = $richTextManager->render(
					$paymentConnector->getCurrentLocalization()->getInstructions(), 'Website', $richTextContext
				);
			}

			$visual = $paymentConnector->getVisual();
			if ($visual && $visualFormats)
			{
				$imagesFormats = new \Rbs\Media\Http\Ajax\V1\ImageFormats($visual);
				$formats = $imagesFormats->getFormatsData($visualFormats);
				if (count($formats))
				{
					$paymentConnectorData['presentation']['visual'] = $formats;
				}
			}
		}

		if (array_key_exists('transaction', $context['dataSetNames'])) {
			$paymentConnectorData['transaction'] = null;

			$transaction = isset($context['data']['transaction']) ? $context['data']['transaction'] : null;
			if ($transaction instanceof \Rbs\Payment\Documents\Transaction) {
				$em = $paymentConnector->getEventManager();
				$args = $em->prepareArgs(['context' => $context]);
				$em->trigger('getPaymentData', $paymentConnector, $args);
				if (isset($args['paymentData']) && is_array($args['paymentData'])) {
					$paymentConnectorData['transaction'] = $args['paymentData'];
				}
			}
		}
		$event->setParam('paymentConnectorData', $paymentConnectorData);
	}

	/**
	 * @api
	 * Default context:
	 *  - *visualFormats, *website, detailed, dataSetNames
	 *  - *data
	 *     - cartId
	 * @param \Rbs\Shipping\Documents\Mode|integer $shippingMode
	 * @param array $context
	 * @return array
	 */
	public function getShippingModeData($shippingMode, array $context)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['shippingMode' => $shippingMode, 'context' => $context]);
		$em->trigger('getShippingModeData', $this, $args);
		if (isset($args['shippingModeData']) && is_array($args['shippingModeData']))
		{
			return $args['shippingModeData'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetShippingModeData(\Change\Events\Event $event)
	{
		if ($event->getParam('shippingModeData') !== null)
		{
			return;
		}

		$shippingMode = $event->getParam('shippingMode');
		if (is_numeric($shippingMode))
		{
			$shippingMode = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shippingMode);
		}
		if (!$shippingMode instanceof \Rbs\Shipping\Documents\Mode)
		{
			return;
		}

		$context = $event->getParam('context');
		if (!is_array($context))
		{
			$context = [];
		}

		// Set default context values.
		$context += ['visualFormats' => [], 'website' => null, 'data' => [], 'detailed' => false, 'dataSetNames' => []];

		$applicationServices = $event->getApplicationServices();

		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$shippingModeData = ['common' => [
			'id' => $shippingMode->getId(),
			'title' => $shippingMode->getCurrentLocalization()->getTitle(),
			'category' => $shippingMode->getCategory(),
		]];

		foreach ($shippingMode->getDeliveryZones() as $deliveryZone)
		{
			$deliveryZoneData = ['id' => $deliveryZone->getId()];
			$country = $deliveryZone->getCountry();
			if ($country) {
				$deliveryZoneData['countryCode'] = $country->getCode();
			}
			$shippingModeData['deliveryZones'][$deliveryZone->getCode()] = $deliveryZoneData;
		}

		if ($context['detailed'])
		{
			$visualFormats = $context['visualFormats'];
			$website = $context['website'];
			$richTextContext = array('website' => $website);
			$richTextManager = $applicationServices->getRichTextManager();

			$shippingModeData['presentation'] = [
				'description' => $richTextManager->render($shippingMode->getCurrentLocalization()
					->getDescription(), 'Website', $richTextContext)];

			$visual = $shippingMode->getVisual();
			if ($visual && $visualFormats)
			{
				$imagesFormats = new \Rbs\Media\Http\Ajax\V1\ImageFormats($visual);
				$formats = $imagesFormats->getFormatsData($visualFormats);
				if (count($formats))
				{
					$shippingModeData['presentation']['visual'] = $formats;
				}
			}
		}

		if (isset($context['data']['cartId']) && ($context['detailed'] || array_key_exists('fee', $context['dataSetNames'])))
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($context['data']['cartId']);
			if ($cart)
			{
				$orderProcess = $this->getOrderProcessByCart($cart);
				if ($orderProcess)
				{
					$fee = $this->getShippingFee($orderProcess, $cart, $shippingMode);
					if ($fee && $fee->getSku())
					{
						$webStore = $applicationServices->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
						$billingArea = $cart->getBillingArea();
						if ($webStore && $billingArea)
						{
							$priceManager = $commerceServices->getPriceManager();

							$price = $priceManager->getPriceBySku($fee->getSku(),
								['webStore' => $webStore, 'billingArea' => $billingArea,
									'targetIds' => $cart->getPriceTargetIds(), 'cart' => $cart,
									'shippingMode' => $shippingMode, 'fee' => $fee]);

							if ($price && ($feesValue = $price->getValue()) > 0)
							{
								$currencyCode = $billingArea->getCurrencyCode();
								$zone = $cart->getZone();
								$precision = $priceManager->getRoundPrecisionByCurrencyCode($currencyCode);

								$shippingModeData['fee']['id'] = $fee->getId();
								$shippingModeData['fee']['currencyCode'] = $currencyCode;
								$shippingModeData['fee']['precision'] = $precision;
								if ($zone)
								{
									$taxes = $commerceServices->getPriceManager()
										->getTaxesApplication($price, $billingArea->getTaxes(), $zone, $billingArea->getCurrencyCode());

									if ($price->isWithTax())
									{
										$amountWithTaxes = $feesValue;
										$amountWithoutTaxes = $commerceServices->getPriceManager()
											->getValueWithoutTax($amountWithTaxes, $taxes);
									}
									else
									{
										$amountWithoutTaxes = $feesValue;
										$amountWithTaxes = $commerceServices->getPriceManager()
											->getValueWithTax($amountWithoutTaxes, $taxes);
									}
									$shippingModeData['fee']['amountWithoutTaxes'] = $priceManager->roundValue($amountWithoutTaxes, $precision);
									$shippingModeData['fee']['amountWithTaxes'] = $priceManager->roundValue($amountWithTaxes, $precision);
								}
								else
								{
									if ($price->isWithTax())
									{
										$shippingModeData['fee']['amountWithTaxes'] = $priceManager->roundValue($feesValue, $precision);
										$shippingModeData['fee']['amountWithoutTaxes'] = null;
									}
									else
									{
										$shippingModeData['fee']['amountWithTaxes'] = null;
										$shippingModeData['fee']['amountWithoutTaxes'] = $priceManager->roundValue($feesValue, $precision);
									}
								}
							}
						}
					}
				}
			}

			if (!isset($shippingModeData['fee']))
			{
				$shippingModeData['fee']['free'] = $applicationServices->getI18nManager()
					->trans('m.rbs.commerce.front.free_shipping_fee', ['ucf']);
			}
		}

		$em = $shippingMode->getEventManager();
		$args = $em->prepareArgs(['context' => $context, 'shippingModeData' => $shippingModeData]);
		$em->trigger('getModeData', $shippingMode, $args);
		if (isset($args['modeData']) && is_array($args['modeData']))
		{
			$shippingModeData = array_merge_recursive($shippingModeData, $args['modeData']);
		}
		$event->setParam('shippingModeData', $shippingModeData);
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
	 * Default context:
	 *  - website
	 *  - data
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $context
	 * @return array
	 */
	public function getShippingFeesEvaluation($orderProcess, $cart, array $context)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart, 'context' => $context]);
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
		/** @var array $context */
		$context = $event->getParam('context');
		$context += ['website' => null, 'data' => []];
		$feesEvaluation = ['countries' => [], 'shippingModes' => [], 'countriesCount' => 0];
		$globalCountries = [];
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');

		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices &&
			$cart instanceof \Rbs\Commerce\Cart\Cart &&
			$orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
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

					$context['data']['cartId'] = $cart->getIdentifier();
					$context['detailed'] = true;

					$shippingModeData = $this->getShippingModeData($shippingMode, $context);
					if (count($shippingModeData))
					{
						$feesEvaluation['shippingModes'][] = $shippingModeData;
					}
				}
			}
		}

		$feesEvaluation['countriesCount'] = count($globalCountries);
		foreach($globalCountries as $key => $value)
		{
			$feesEvaluation['countries'][] = ['code' => $key, 'title' => $value];
		}
		$event->setParam('feesEvaluation', $feesEvaluation);
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Commerce\Documents\Process|integer $process
	 * @param array $context
	 * @return array
	 */
	public function getProcessData($process, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['process' => $process, 'context' => $context]);
		$em->trigger('getProcessData', $this, $eventArgs);
		if (isset($eventArgs['processData']))
		{
			$processData = $eventArgs['processData'];
			if (is_object($processData))
			{
				$callable = [$processData, 'toArray'];
				if (is_callable($callable))
				{
					$processData = call_user_func($callable);
				}
			}
			if (is_array($processData))
			{
				return $processData;
			}
		}
		return [];
	}

	/**
	 * Input params: process, context
	 * Output param: processData
	 * @param \Change\Events\Event $event
	 */
	public function  onDefaultGetProcessData(\Change\Events\Event $event)
	{
		if (!$event->getParam('processData'))
		{
			$process = $event->getParam('process');
			if (is_numeric($process))
			{
				$process = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($process);
			}

			if ($process instanceof \Rbs\Commerce\Documents\Process)
			{
				$event->setParam('process', $process);
				$processDataComposer = new \Rbs\Commerce\Process\ProcessDataComposer($event);
				$event->setParam('processData', $processDataComposer->toArray());
			}
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data :
	 *    address
	 * @api
	 * @param \Rbs\Commerce\Documents\Process|integer $process
	 * @param array $context
	 * @return array
	 */
	public function getShippingModesDataByAddress($process, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['process' => $process, 'context' => $context]);
		$em->trigger('getShippingModesDataByAddress', $this, $eventArgs);
		if (isset($eventArgs['shippingModesData']) && is_array($eventArgs['shippingModesData']))
		{
			return $eventArgs['shippingModesData'];
		}
		return [];
	}

	/**
	 * Input params: process, context
	 * Output param: processData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetShippingModesDataByAddress(\Change\Events\Event $event)
	{
		if (!$event->getParam('shippingModesData'))
		{
			$process = $event->getParam('process');
			if (is_numeric($process))
			{
				$process = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($process);
			}
			if ($process instanceof \Rbs\Commerce\Documents\Process)
			{
				$context = $event->getParam('context');
				$data = isset($context['data']) && is_array($context['data']) ? $context['data'] : [];
				$addressData = isset($data['address']) && is_array($data['address']) ? $data['address'] : [];
				if ($addressData)
				{
					$shippingModesData = [];
					$address = new \Rbs\Geo\Address\BaseAddress($addressData);
					$shippingModes = $this->getCompatibleShippingModes($process, $context);

					foreach ($shippingModes as $shippingMode)
					{
						if ($this->isValidAddressForShippingMode($shippingMode, $address))
						{
							$shippingModesData[] = $this->getShippingModeData($shippingMode, $context);
						}
					}
					$event->setParam('shippingModesData', $shippingModesData);
				}
			}
		}
	}

	/**
	 * Default context usage:
	 *  - website
	 *  - dataSetNames: connectors
	 *  - data:
	 *    - returnSuccessFunction
	 * @param \Rbs\Commerce\Cart\Cart|string $cart
	 * @param array $context
	 * @return array|null
	 */
	public function getCartTransactionData($cart, array $context)
	{
		if (is_string($cart)) {
			$cart = $this->getCartManager()->getCartByIdentifier($cart);
		}

		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			if (!$cart->isLocked())
			{
				if (!$this->getCartManager()->lockCart($cart))
				{
					$errorsData = ['errors' => []];
					foreach ($cart->getErrors() as $error)
					{
						$errorsData['errors'][] = $error->toArray();
					}
					return $errorsData;
				}
			}

			$context += ['website' => null, 'data' => [], 'dataSetNames' => []];
			/** @var \Change\Presentation\Interfaces\Website $website */
			$website = $context['website'];

			/** @var array $data */
			$data = $context['data'];

			$contextData = $cart->getContext()->toArray();
			$contextData['from'] = 'cart';
			$contextData['guestCheckout'] = !$cart->getUserId();
			if ($website instanceof \Change\Presentation\Interfaces\Website) {
				$contextData['websiteId'] = $website->getId();
				$contextData['LCID'] = $website->getLCID();
			}
			$contextData['returnSuccessFunction'] = isset($data['returnSuccessFunction']) ? $data['returnSuccessFunction'] :'Rbs_Commerce_PaymentReturn';

			$transaction = $this->getNewTransaction(
				$cart->getIdentifier(), $cart->getPaymentAmount(), $cart->getCurrencyCode(),
				$cart->getEmail(), $cart->getUserId(), $cart->getOwnerId(),
				$contextData
			);

			if ($transaction)
			{
				$transactionData = ['common' => ['id' => $transaction->getId(),
					'amount' => $transaction->getAmount(), 'currencyCode' => $transaction->getCurrencyCode(),
					'targetIdentifier' => $transaction->getTargetIdentifier()]];
				$transactionContext = $transaction->getContextData();
				$transactionData['context'] = is_array($transactionContext) && count($transactionContext) ? $transactionContext : null;
				$process = $this->getOrderProcessByCart($cart);
				if ($process && array_key_exists('connectors', $context['dataSetNames']))
				{
					$transactionData['connectors'] = [];
					foreach ($process->getPaymentConnectors() as $paymentConnector)
					{
						$context['detailed'] = false;
						$context['data']['transaction'] = $transaction;
						$context['dataSetNames']['transaction'] = true;
						if ($paymentConnector->isCompatibleWith($cart))
						{
							$paymentConnectorData = $this->getPaymentConnectorData($paymentConnector, $context);
							if (count($paymentConnectorData))
							{
								$transactionData['connectors'][] = $paymentConnectorData;
							}
						}
					}
				}

				return $transactionData;
			}
		}
		return null;
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
				$order->setLinesAmountWithoutTaxes($cart->getLinesAmountWithoutTaxes());
				$order->setLinesTaxes($cart->getLinesTaxes());
				$order->setLinesAmountWithTaxes($cart->getLinesAmountWithTaxes());

				$order->setAddress($cart->getAddress()->toArray());
				$order->setShippingModes($cart->getShippingModes());

				$order->setCoupons($cart->getCoupons());
				$order->setFees($cart->getFees());
				$order->setDiscounts($cart->getDiscounts());

				$order->setTotalAmountWithoutTaxes($cart->getTotalAmountWithoutTaxes());
				$order->setTotalTaxes($cart->getTotalTaxes());
				$order->setTotalAmountWithTaxes($cart->getTotalAmountWithTaxes());

				$order->setCreditNotes($cart->getCreditNotes());
				$order->setPaymentAmount($cart->getPaymentAmount());

				$order->setProcessingStatus(\Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING);

				// If there is a transaction, get the website id and add it to context. This is useful for mail sending.
				$transaction = $documentManager->getDocumentInstance($cart->getTransactionId());
				if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
				{
					$transactionContext = $transaction->getContextData();
					if (is_array($transactionContext) && isset($transactionContext['websiteId']))
					{
						$order->getContext()->set('websiteId', $transactionContext['websiteId']);
					}
				}

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
			$jobManager->createNewJob('Rbs_Commerce_Notification', $argument);
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
					$invoice->setAmountWithTax($order->getPaymentAmount());
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
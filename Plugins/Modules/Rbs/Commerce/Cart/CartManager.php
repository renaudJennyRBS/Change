<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartManager
 */
class CartManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CartManager';

	/**
	 * @var \Rbs\Stock\Services\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @param \Rbs\Price\PriceManager $priceManager
	 * @return $this
	 */
	public function setPriceManager(\Rbs\Price\PriceManager $priceManager)
	{
		$this->priceManager = $priceManager;
		return $this;
	}

	/**
	 * @return \Rbs\Price\PriceManager
	 */
	protected function getPriceManager()
	{
		return $this->priceManager;
	}

	/**
	 * @param \Rbs\Stock\Services\StockManager $stockManager
	 * @return $this
	 */
	public function setStockManager(\Rbs\Stock\Services\StockManager $stockManager)
	{
		$this->stockManager = $stockManager;
		return $this;
	}

	/**
	 * @return \Rbs\Stock\Services\StockManager
	 */
	protected function getStockManager()
	{
		return $this->stockManager;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 * @return $this
	 */
	public function setLogging($logging)
	{
		$this->logging = $logging;
		return $this;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->logging;
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CartManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('validCart', [$this, 'onDefaultValidCart'], 5);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalize'], 5);
		$eventManager->attach('getFiltersDefinition', [$this, 'onDefaultGetFiltersDefinition'], 5);
		$eventManager->attach('isValidFilter', [$this, 'onDefaultIsValidFilter'], 5);
	}

	/**
	 * @param string $cartIdentifier
	 * @return \Rbs\Commerce\Cart\Cart|null
	 */
	public function getCartByIdentifier($cartIdentifier)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cartIdentifier' => $cartIdentifier));
		$this->getEventManager()->trigger('getCartByIdentifier', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			return $args['cart'];
		}
		return null;
	}

	/**
	 * Return Merged cart
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\Cart $cartToMerge
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function mergeCart($cart, $cartToMerge)
	{
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cartToMerge instanceof \Rbs\Commerce\Cart\Cart)
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'cartToMerge' => $cartToMerge));
			$this->getEventManager()->trigger('mergeCart', $this, $args);
			if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Cart\Cart)
			{
				return $args['cart'];
			}
		}
		return $cart;
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @param array $context
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function getNewCart($webStore = null, $billingArea = null, $zone = null, array $context = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(
			array('webStore' => $webStore, 'billingArea' => $billingArea, 'zone' => $zone, 'context' => $context));
		$this->getEventManager()->trigger('getNewCart', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			/** @var $cart \Rbs\Commerce\Cart\Cart */
			$cart = $args['cart'];
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$cart->setWebStoreId($webStore->getId());
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			if ($billingArea instanceof \Rbs\Price\Tax\BillingAreaInterface)
			{
				$cart->setBillingArea($billingArea);
			}

			$cart->setZone($zone);
			if (count($context))
			{
				foreach ($context as $key => $value)
				{
					if (is_string($key) && isset($value))
					{
						$cart->getContext()->set($key, $value);
					}
				}
			}
			return $cart;
		}
		throw new \RuntimeException('Unable to get a new cart', 999999);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function getUnlockedCart($cart)
	{
		if (!$cart->isLocked())
		{
			return $cart;
		}

		$newCart = $this->getNewCart($cart->getWebStore(), $cart->getBillingArea(), $cart->getZone());
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart, 'newCart' => $newCart));
		$this->getEventManager()->trigger('getUnlockedCart', $this, $args);
		if (isset($args['newCart']) && $args['newCart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			return $args['newCart'];
		}
		throw new \RuntimeException('Unable to get a new cart', 999999);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	public function saveCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		if (!$cart->isLocked())
		{
			$this->validCart($cart);
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart));
			$this->getEventManager()->trigger('saveCart', $this, $args);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return boolean
	 */
	public function validCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		try
		{
			$cart->setErrors(array());

			$em = $this->getEventManager();
			$args = $em->prepareArgs(['cart' => $cart, 'errors' => new \ArrayObject()]);

			$this->getEventManager()->trigger('validCart', $this, $args);
			if (isset($args['errors']) && (is_array($args['errors']) || $args['errors'] instanceof \Traversable))
			{
				foreach ($args['errors'] as $error)
				{
					$cart->addError($error);
				}
			}
		}
		catch (\Exception $e)
		{
			$cart->addError(new CartError($e->getMessage()));
		}
		return !$cart->hasError();
	}

	/**
	 * Event Params: cart, errors, lockForOwnerId, commerceServices
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultValidCart(\Change\Events\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			/* @var $errors \ArrayObject */
			$errors = $event->getParam('errors');

			if (!$cart->getWebStoreId())
			{
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_webstore', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			foreach ($cart->getLines() as $line)
			{
				if (!$line->getQuantity())
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_quantity', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_sku', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getUnitPriceValue() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_price', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
			}

			$reservations = $commerceServices->getCartManager()->getReservations($cart);
			if (count($reservations))
			{
				$unreserved = $commerceServices->getStockManager()->setReservations($cart->getIdentifier(), $reservations);
				if (count($unreserved))
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.cart_reservation_error', array('ucf'));
					$err = new CartError($message);
					$errors[] = $err;
				}
			}
			else
			{
				$commerceServices->getStockManager()->unsetReservations($cart->getIdentifier());
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return boolean
	 */
	public function lockCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		if (!$cart->isLocked())
		{
			try
			{
				if ($this->validCart($cart))
				{
					$em = $this->getEventManager();
					if (!$cart->getOwnerId())
					{
						$args = $em->prepareArgs(array('cart' => $cart, 'ownerId' => 0));
						$this->getEventManager()->trigger('getOwnerId', $this, $args);
						if (isset($args['ownerId']) && $args['ownerId'])
						{
							$cart->setOwnerId($args['ownerId']);
						}
						else
						{
							$cart->setOwnerId($cart->getUserId());
						}
					}
					$args = $em->prepareArgs(array('cart' => $cart));
					$this->getEventManager()->trigger('lockCart', $this, $args);
				}
				return $cart->isLocked();
			}
			catch (\Exception $e)
			{
				$this->getLogging()->exception($e);
			}
		}
		return false;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return boolean
	 */
	public function startProcessingCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		if (!$cart->isProcessing())
		{
			try
			{
				if (!$cart->isLocked())
				{
					throw new \RuntimeException('Can\'t process an unlocked cart!');
				}
				$em = $this->getEventManager();
				$args = $em->prepareArgs(array('cart' => $cart));
				$this->getEventManager()->trigger('startProcessingCart', $this, $args);
				return $cart->isProcessing();
			}
			catch (\Exception $e)
			{
				$this->getLogging()->exception($e);
			}
		}
		return false;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param integer $transactionId
	 * @return integer|null
	 */
	public function affectTransactionId(\Rbs\Commerce\Cart\Cart $cart, $transactionId)
	{
		if ($cart->isLocked() && is_numeric($transactionId))
		{
			try
			{
				if (!$cart->isProcessing())
				{
					$this->startProcessingCart($cart);
				}
				$em = $this->getEventManager();
				$args = $em->prepareArgs(array('cart' => $cart, 'transactionId' => $transactionId));
				$this->getEventManager()->trigger('affectTransactionId', $this, $args);
				return $cart->getTransactionId();
			}
			catch (\Exception $e)
			{
				$this->getLogging()->exception($e);
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param integer|\Rbs\Order\Documents\Order $order
	 * @return integer|null
	 */
	public function affectOrder(\Rbs\Commerce\Cart\Cart $cart, $order)
	{
		if ($cart->isLocked())
		{
			try
			{
				$em = $this->getEventManager();
				$args = $em->prepareArgs(array('cart' => $cart, 'order' => $order));
				$this->getEventManager()->trigger('affectOrder', $this, $args);
			}
			catch (\Exception $e)
			{
				$this->getLogging()->exception($e);
			}
		}
		return $cart->getOrderId();
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param integer|\Rbs\User\Documents\User $user
	 */
	public function affectUser(\Rbs\Commerce\Cart\Cart $cart, $user)
	{
		try
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'user' => $user));
			$this->getEventManager()->trigger('affectUser', $this, $args);
		}
		catch (\Exception $e)
		{
			$this->getLogging()->exception($e);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string|\Rbs\Commerce\Cart\CartLine $key
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function getLineByKey(\Rbs\Commerce\Cart\Cart $cart, $key)
	{
		if ($key instanceof \Rbs\Commerce\Cart\CartLine)
		{
			$lineKey = $key->getKey();
		}
		else
		{
			$lineKey = strval($key);
		}
		return $cart->getLineByKey($lineKey);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\CartLine|array $parameters
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function addLine(\Rbs\Commerce\Cart\Cart $cart, $parameters)
	{
		if ($parameters instanceof \Rbs\Commerce\Cart\CartLine)
		{
			$line = $parameters;
		}
		elseif (is_array($parameters))
		{
			$line = $cart->getNewLine($parameters);
		}
		else
		{
			$line = null;
		}

		if ($line && $line->getKey() && count($line->getItems()))
		{
			$cart->appendLine($line);
			$this->refreshCartLine($cart, $line);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a valid parameters list', 999999);
		}
		return $line;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string|\Rbs\Commerce\Cart\CartLine $lineKey
	 * @param integer $newQuantity
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function updateLineQuantityByKey(\Rbs\Commerce\Cart\Cart $cart, $lineKey, $newQuantity)
	{
		if ($lineKey instanceof \Rbs\Commerce\Cart\CartLine)
		{
			$lineKey = $lineKey->getKey();
		}
		else
		{
			$lineKey = strval($lineKey);
		}

		$line = $cart->updateLineQuantity($lineKey, $newQuantity);
		if ($line instanceof \Rbs\Commerce\Cart\CartLine)
		{
			$this->refreshCartLine($cart, $line);
			return $line;
		}
		else
		{
			throw new \RuntimeException('Cart line not found for key: ' . $lineKey, 999999);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string|\Rbs\Commerce\Cart\CartLine $key
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function removeLineByKey(\Rbs\Commerce\Cart\Cart $cart, $key)
	{
		if ($key instanceof \Rbs\Commerce\Cart\CartLine)
		{
			$lineKey = $key->getKey();
		}
		else
		{
			$lineKey = strval($key);
		}

		$line = $cart->removeLineByKey($lineKey);
		if ($line instanceof \Rbs\Commerce\Cart\CartLine)
		{
			return $line;
		}
		else
		{
			throw new \RuntimeException('Cart line not found for key: ' . $lineKey, 999999);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	public function normalize(\Rbs\Commerce\Cart\Cart $cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['cart' => $cart]);
		$this->getEventManager()->trigger('normalize', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalize(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$cart->setDocumentManager($event->getApplicationServices()->getDocumentManager());
			$webStore = $cart->getWebStore();
			if ($webStore)
			{
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			foreach ($cart->getLines() as $index => $line)
			{
				$line->setIndex($index);
				$this->refreshCartLine($cart, $line);
			}

			$this->refreshLinesPriceValue($cart);
			$cart->setTaxesValues($cart->getLinesTaxesValues());
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\CartLine $line
	 */
	protected function refreshCartLine(\Rbs\Commerce\Cart\Cart $cart, \Rbs\Commerce\Cart\CartLine $line)
	{
		$webStore = $cart->getWebStore();
		$billingArea = $cart->getBillingArea();
		if (!$webStore || !$billingArea)
		{
			return;
		}

		$pricesValueWithTax = $cart->getPricesValueWithTax();
		foreach ($line->getItems() as $item)
		{
			$sku = $this->getStockManager()->getSkuByCode($item->getCodeSKU());
			if ($sku)
			{
				if (!$item->getOptions()->get('lockedPrice', false))
				{
					$price = $this->getPriceManager()->getPriceBySku($sku,
						['webStore' => $webStore, 'billingArea' => $billingArea, 'cart' => $cart, 'cartLine' => $line]);
					$item->setPrice($price);
				}
			}
			$price = $item->getPrice();
			if ($price)
			{
				$item->getPrice()->setWithTax($pricesValueWithTax);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	protected function refreshLinesPriceValue($cart)
	{
		$priceManager = $this->getPriceManager();

		/* @var $linesTaxesValues \Rbs\Price\Tax\TaxApplication[] */
		$linesTaxesValues = [];
		$currencyCode = $cart->getCurrencyCode();
		$zone = $cart->getZone();
		$taxes = $cart->getTaxes();
		if (!$currencyCode || !$zone || count($taxes) == 0)
		{
			$taxes = null;
		}

		foreach ($cart->getLines() as $line)
		{
			$taxesLine = [];
			$priceValue = null;
			$priceValueWithTax = null;

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
								$taxesLine = $priceManager->addTaxesApplication($taxesLine, $taxArray);
							}

							if ($price->isWithTax())
							{
								$priceValueWithTax += $lineItemValue;
								$priceValue += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);
							}
							else
							{
								$priceValue += $lineItemValue;
								$priceValueWithTax = $priceManager->getValueWithTax($lineItemValue, $taxArray);
							}
						}
						else
						{
							if ($price->isWithTax())
							{
								$priceValueWithTax += $lineItemValue;
							}
							else
							{
								$priceValue += $lineItemValue;
							}
						}
					}
				}
			}

			$line->setTaxes($taxesLine);
			$line->setPriceValueWithTax($priceValueWithTax);
			$line->setPriceValue($priceValue);
			$linesTaxesValues = $priceManager->addTaxesApplication($linesTaxesValues, $taxesLine);
		}
		$cart->setLinesTaxesValues($linesTaxesValues);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Commerce\Cart\CartReservation[]
	 */
	public function getReservations(\Rbs\Commerce\Cart\Cart $cart)
	{
		/* @var $cartReservations \Rbs\Commerce\Cart\CartReservation[] */
		$cartReservations = array();
		$cartWebStoreId = $cart->getWebStoreId();
		if ($cartWebStoreId)
		{
			foreach ($cart->getLines() as $line)
			{
				$lineQuantity = $line->getQuantity();
				if ($lineQuantity)
				{
					$lineWebStoreId = $line->getOptions()->get('webStoreId', $cartWebStoreId);
					foreach ($line->getItems() as $item)
					{
						if ($item->getReservationQuantity())
						{
							$webStoreId = $item->getOptions()->get('webStoreId', $lineWebStoreId);
							$res = new \Rbs\Commerce\Cart\CartReservation($item->getCodeSKU(), $webStoreId);
							$key = $res->getKey();
							$resQtt = $lineQuantity * $item->getReservationQuantity();
							if (isset($cartReservations[$key]))
							{
								$res = $cartReservations[$key];
								$res->addQuantity($resQtt);
							}
							else
							{
								$cartReservations[$key] = $res->setQuantity($resQtt);
							}
						}
					}
				}
			}
		}
		return $cartReservations;
	}

	/**
	 * @return array
	 */
	public function getFiltersDefinition()
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['filtersDefinition' => []]);
		$em->trigger('getFiltersDefinition', $this, $args);
		return isset($args['filtersDefinition'])
		&& is_array($args['filtersDefinition']) ? array_values($args['filtersDefinition']) : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFiltersDefinition($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$group = $i18nManager->trans('m.rbs.admin.admin.properties', ['ucf']);
		$filtersDefinition = $event->getParam('filtersDefinition');
		$definition = ['name' => 'linesPriceValue', 'parameters' => ['propertyName' => 'linesPriceValue']];
		$definition['config']['listLabel'] = $i18nManager->trans('m.rbs.commerce.admin.lines_price_value_filter', ['ucf']);
		$definition['config']['group'] = $group;
		$definition['config']['label'] = $i18nManager->trans('m.rbs.commerce.admin.lines_price_value', ['ucf']);
		$definition['directiveName'] = 'rbs-document-filter-property-number';
		$filtersDefinition[] = $definition;
		$event->setParam('filtersDefinition', $filtersDefinition);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $filter
	 * @return boolean
	 */
	public function isValidFilter(\Rbs\Commerce\Cart\Cart $cart, $filter)
	{
		if (is_array($filter) && isset($filter['name']))
		{
			$name = $filter['name'];
			if ($name === 'group')
			{
				if (isset($filter['operator']) && isset($filter['filters']) && is_array($filter['filters']))
				{
					return $this->isValidGroupFilters($cart, $filter['operator'], $filter['filters']);
				}
			}
			else
			{
				$em = $this->getEventManager();
				$args = $em->prepareArgs(['cart' => $cart, 'name' => $name, 'filter' => $filter]);
				$em->trigger('isValidFilter', $this, $args);
				if (isset($args['valid']))
				{
					return ($args['valid'] == true);
				}
			}
		}
		return true;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidFilter($event)
	{
		$name = $event->getParam('name');
		if ($name === 'linesPriceValue')
		{
			$filter = $event->getParam('filter');
			if (isset($filter['parameters']['value']) && isset($filter['parameters']['operator']))
			{
				/** @var $cart \Rbs\Commerce\Cart\Cart */
				$cart = $event->getParam('cart');
				$value = $filter['parameters']['value'];
				$operator = $filter['parameters']['operator'];

				if ($operator == 'gte')
				{
					$event->setParam('valid', $cart->getPriceValue() >= $value);
				}
				else
				{
					$event->setParam('valid', $cart->getPriceValue() <= $value);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string $operator
	 * @param array $filters
	 * @return boolean
	 */
	protected function isValidGroupFilters($cart, $operator, $filters)
	{
		if (!count($filters))
		{
			return true;
		}
		if ($operator === 'OR')
		{
			foreach ($filters as $filter)
			{
				if ($this->isValidFilter($cart, $filter))
				{
					return true;
				}
			}
			return false;
		}
		else
		{
			foreach ($filters as $filter)
			{
				if (!$this->isValidFilter($cart, $filter))
				{
					return false;
				}
			}
			return true;
		}
	}
}
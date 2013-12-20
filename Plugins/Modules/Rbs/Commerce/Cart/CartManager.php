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
	 * @var \Rbs\Price\Tax\TaxManager
	 */
	protected $taxManager;

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
	 * @param \Rbs\Price\Tax\TaxManager $taxManager
	 * @return $this
	 */
	public function setTaxManager(\Rbs\Price\Tax\TaxManager $taxManager)
	{
		$this->taxManager = $taxManager;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxManager
	 */
	protected function getTaxManager()
	{
		return $this->taxManager;
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
			if ($webStore instanceof \Change\Documents\AbstractDocument)
			{
				$cart->setWebStoreId($webStore->getId());
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
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_quantity', array('ucf'), array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_sku', array('ucf'), array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getUnitPriceValue() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_price', array('ucf'), array('number' => $line->getIndex() + 1));
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
	 * @param integer $transactionId
	 * @return integer|null
	 */
	public function affectTransactionId(\Rbs\Commerce\Cart\Cart $cart, $transactionId)
	{
		if ($cart->isLocked() && is_numeric($transactionId))
		{
			try
			{
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
		return $cart->getOrdered();
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
			foreach ($cart->getLines() as $index => $line)
			{
				$line->setIndex($index);
				$this->refreshCartLine($cart, $line);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\CartLine $line
	 */
	protected function refreshCartLine(\Rbs\Commerce\Cart\Cart $cart, \Rbs\Commerce\Cart\CartLine $line)
	{
		$webStoreId = $cart->getWebStoreId();
		$billingArea = $cart->getBillingArea();
		foreach ($line->getItems() as $item)
		{
			$sku = $this->getStockManager()->getSkuByCode($item->getCodeSKU());
			if ($sku)
			{
				if (!$item->getOptions()->get('lockedPrice', false))
				{
					$price = $this->getPriceManager()->getPriceBySku($sku,
						['webStore' => $webStoreId, 'billingArea' => $billingArea, 'cart' => $cart, 'cartLine' => $line]);
					if ($price)
					{
						$priceValue = $price->getValue();
						$item->setPrice($price);
						$item->setCartTaxes($this->getTaxManager()
							->getTaxByValue($priceValue, $price->getTaxCategories(), $cart->getBillingArea(), $cart->getZone()));
					}
				}
			}
		}
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
}
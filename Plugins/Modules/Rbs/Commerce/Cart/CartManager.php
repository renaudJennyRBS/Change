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
	 * @var \Rbs\Price\Services\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Rbs\Price\Services\TaxManager
	 */
	protected $taxManager;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @param \Rbs\Price\Services\PriceManager $priceManager
	 * @return $this
	 */
	public function setPriceManager(\Rbs\Price\Services\PriceManager $priceManager)
	{
		$this->priceManager = $priceManager;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Services\PriceManager
	 */
	protected  function getPriceManager()
	{
		return $this->priceManager;
	}

	/**
	 * @param \Rbs\Price\Services\TaxManager $taxManager
	 * @return $this
	 */
	public function setTaxManager(\Rbs\Price\Services\TaxManager $taxManager)
	{
		$this->taxManager = $taxManager;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Services\TaxManager
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
	 * @param string $cartIdentifier
	 * @return \Rbs\Commerce\Interfaces\Cart|null
	 */
	public function getCartByIdentifier($cartIdentifier)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cartIdentifier' => $cartIdentifier));
		$this->getEventManager()->trigger('getCartByIdentifier', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			return $args['cart'];
		}
		return null;
	}

	/**
	 * Return Merged cart
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param \Rbs\Commerce\Interfaces\Cart $cartToMerge
	 * @return \Rbs\Commerce\Interfaces\Cart
	 */
	public function mergeCart($cart, $cartToMerge)
	{
		if ($cart instanceof \Rbs\Commerce\Interfaces\Cart && $cartToMerge instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'cartToMerge' => $cartToMerge));
			$this->getEventManager()->trigger('mergeCart', $this, $args);
			if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Interfaces\Cart)
			{
				return $args['cart'];
			}
		}
		return $cart;
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @param string $zone
	 * @param array $context
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\Cart
	 */
	public function getNewCart($webStore = null, $billingArea = null, $zone = null, array $context = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(
			array('webStore' => $webStore, 'billingArea' => $billingArea, 'zone' => $zone, 'context' => $context));
		$this->getEventManager()->trigger('getNewCart', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			return $args['cart'];
		}
		throw new \RuntimeException('Unable to get a new cart', 999999);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 */
	public function saveCart(\Rbs\Commerce\Interfaces\Cart $cart)
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
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param integer $lockForOwnerId
	 * @return boolean
	 */
	public function validCart(\Rbs\Commerce\Interfaces\Cart $cart, $lockForOwnerId = null)
	{
		try
		{
			$cart->setErrors(array());

			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'errors' => new \ArrayObject(),
				'lockForOwnerId' => $lockForOwnerId));

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
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param integer $ownerId
	 * @return bool
	 */
	public function lockCart(\Rbs\Commerce\Interfaces\Cart $cart, $ownerId)
	{
		if (!$cart->isLocked())
		{
			try
			{
				if ($this->validCart($cart, $ownerId))
				{
					$em = $this->getEventManager();
					$args = $em->prepareArgs(array('cart' => $cart, 'ownerId' => $ownerId));
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
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param string|\Rbs\Commerce\Interfaces\CartLineConfig|\Rbs\Commerce\Interfaces\CartLine $key
	 * @return \Rbs\Commerce\Interfaces\CartLine|null
	 */
	public function getLineByKey(\Rbs\Commerce\Interfaces\Cart $cart, $key)
	{
		if ($key instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			$lineKey = $key->getKey();
		}
		elseif ($key instanceof \Rbs\Commerce\Interfaces\CartLineConfig)
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
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param \Rbs\Commerce\Interfaces\CartLineConfig $cartLineConfig
	 * @param integer $quantity
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function addLine(\Rbs\Commerce\Interfaces\Cart $cart, $cartLineConfig, $quantity = 1)
	{
		if ($cartLineConfig instanceof \Rbs\Commerce\Interfaces\CartLineConfig)
		{
			$line = $cart->getNewLine($cartLineConfig, $quantity);
			$cart->appendLine($line);
			$this->refreshCartLine($cart, $line);
			return $line;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a CartLineConfig', 999999);
		}
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param string|\Rbs\Commerce\Interfaces\CartLineConfig|\Rbs\Commerce\Interfaces\CartLine $lineKey
	 * @param integer $newQuantity
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function updateLineQuantityByKey(\Rbs\Commerce\Interfaces\Cart $cart, $lineKey, $newQuantity)
	{
		if ($lineKey instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			$lineKey = $lineKey->getKey();
		}
		elseif ($lineKey instanceof \Rbs\Commerce\Interfaces\CartLineConfig)
		{
			$lineKey = $lineKey->getKey();
		}
		else
		{
			$lineKey = strval($lineKey);
		}

		$line = $cart->updateLineQuantity($lineKey, $newQuantity);
		if ($line instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			$this->refreshCartLine($cart, $line);
			return $line;
		}
		else
		{
			throw new \RuntimeException('Cart line not found for key: ' . $lineKey , 999999);
		}
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param string|\Rbs\Commerce\Interfaces\CartLineConfig|\Rbs\Commerce\Interfaces\CartLine $lineKey
	 * @param \Rbs\Commerce\Interfaces\CartLineConfig $cartLineConfig
	 * @return null|\Rbs\Commerce\Interfaces\CartLine
	 */
	public function updateLineByKey($cart, $lineKey, $cartLineConfig)
	{
		if ($lineKey instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			$lineKey = $lineKey->getKey();
		}
		elseif ($lineKey instanceof \Rbs\Commerce\Interfaces\CartLineConfig)
		{
			$lineKey = $lineKey->getKey();
		}
		else
		{
			$lineKey = strval($lineKey);
		}

		$line = $cart->getLineByKey($lineKey);
		if ($line && !$cart->getLineByKey($cartLineConfig->getKey()))
		{
			$newLine = $cart->getNewLine($cartLineConfig, $line->getQuantity());
			$cart->insertLineAt($newLine, $line->getNumber());
			$cart->removeLineByKey($lineKey);
			return $newLine;
		}
		return null;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param string|\Rbs\Commerce\Interfaces\CartLineConfig|\Rbs\Commerce\Interfaces\CartLine $key
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function removeLineByKey(\Rbs\Commerce\Interfaces\Cart $cart, $key)
	{
		if ($key instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			$lineKey = $key->getKey();
		}
		elseif ($key instanceof \Rbs\Commerce\Interfaces\CartLineConfig)
		{
			$lineKey = $key->getKey();
		}
		else
		{
			$lineKey = strval($key);
		}

		$line = $cart->removeLineByKey($lineKey);
		if ($line instanceof \Rbs\Commerce\Interfaces\CartLine)
		{
			return $line;
		}
		else
		{
			throw new \RuntimeException('Cart line not found for key: ' . $lineKey , 999999);
		}
	}

	protected function refreshCartLine(\Rbs\Commerce\Interfaces\Cart $cart, \Rbs\Commerce\Interfaces\CartLine $line)
	{
		$lineWebStoreId = $line->getOptions()->get('webStoreId', $cart->getWebStoreId());
		foreach ($line->getItems() as $item)
		{
			$sku = $this->getStockManager()->getSkuByCode($item->getCodeSKU());
			if ($sku)
			{
				if (!$item->getOptions()->get('lockedPrice', false))
				{
					$webStoreId = $item->getOptions()->get('webStoreId', $lineWebStoreId);
					$price = $this->getPriceManager()->getPriceBySku($sku, $webStoreId, $cart->getBillingArea());
					if ($price)
					{
						$priceValue = $price->getValue();
						$cart->updateItemPrice($item, $priceValue);
						$taxApplicationArray = $this->getTaxManager()->getTaxByValue($priceValue, $price->getTaxCategories(), $cart->getBillingArea(), $cart->getZone());
						$cart->updateItemTaxes($item, $taxApplicationArray);
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @return \Rbs\Commerce\Cart\CartReservation[]
	 */
	public function getReservations(\Rbs\Commerce\Interfaces\Cart $cart)
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
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartManager
 */
class CartManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CartManager';

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var array
	 */
	protected $cachedCarts = [];

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

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
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @return $this
	 */
	public function setStockManager(\Rbs\Stock\StockManager $stockManager)
	{
		$this->stockManager = $stockManager;
		return $this;
	}

	/**
	 * @return \Rbs\Stock\StockManager
	 */
	protected function getStockManager()
	{
		return $this->stockManager;
	}



	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CartManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('validCart', [$this, 'onDefaultValidCart'], 5);

		$eventManager->attach('normalize', [$this, 'onDefaultNormalize'], 5);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalizeModifiers'], 4);

		$eventManager->attach('getFiltersDefinition', [$this, 'onDefaultGetFiltersDefinition'], 5);
		$eventManager->attach('isValidFilter', [$this, 'onDefaultIsValidFilter'], 5);

		$eventManager->attach('getNewCart', [$this, 'onDefaultGetNewCart'], 5);
		$eventManager->attach('saveCart', [$this, 'onDefaultSaveCart'], 5);
		$eventManager->attach('getCartByIdentifier', [$this, 'onDefaultGetCartByIdentifier'], 5);
		$eventManager->attach('mergeCart', [$this, 'onDefaultMergeCart'], 5);
		$eventManager->attach('getUnlockedCart', [$this, 'onDefaultGetUnlockedCart'], 5);
		$eventManager->attach('lockCart', [$this, 'onDefaultLockCart'], 5);
		$eventManager->attach('startProcessingCart', [$this, 'onDefaultStartProcessingCart'], 5);
		$eventManager->attach('affectTransactionId', [$this, 'onDefaultAffectTransactionId'], 5);
		$eventManager->attach('affectOrder', [$this, 'onDefaultAffectOrder'], 5);
		$eventManager->attach('affectUser', [$this, 'onDefaultAffectUser'], 5);
		$eventManager->attach('deleteCart', [$this, 'onDefaultDeleteCart'], 5);
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
		$args = $em->prepareArgs(['webStore' => $webStore, 'billingArea' => $billingArea, 'zone' => $zone,
			'context' => $context]);
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetNewCart(\Change\Events\Event $event)
	{
		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$cart = null;
		try
		{
			$tm->begin();

			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$date = new \DateTime();

			$qb->insert($fb->table('rbs_commerce_dat_cart'),
				$fb->column('creation_date'), $fb->column('last_update'));
			$qb->addValue($fb->dateTimeParameter('creationDate'));
			$qb->addValue($fb->dateTimeParameter('lastUpdate'));

			$iq = $qb->insertQuery();
			$iq->bindParameter('creationDate', $date);
			$iq->bindParameter('lastUpdate', $date);
			$iq->execute();

			$storageId = $iq->getDbProvider()->getLastInsertId('rbs_commerce_dat_cart');
			$identifier = sha1($storageId . '-' . $date->getTimestamp());

			$cart = new Cart($identifier, $this);
			$cart->lastUpdate($date);
			$cart->getContext()->set('storageId', $storageId);

			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('identifier'), $fb->parameter('identifier'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('cartData', serialize($cart));
			$uq->bindParameter('id', $storageId);
			$uq->execute();

			$this->cachedCarts = array();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('cart', $cart);
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultSaveCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$cart->lastUpdate(new \DateTime());
			try
			{
				$tm->begin();
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();

				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
				$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
				$qb->assign($fb->column('store_id'), $fb->integerParameter('webStoreId'));
				$qb->assign($fb->column('user_id'), $fb->integerParameter('userId'));
				$qb->assign($fb->column('owner_id'), $fb->integerParameter('ownerId'));
				$qb->assign($fb->column('transaction_id'), $fb->integerParameter('transactionId'));
				$qb->assign($fb->column('order_id'), $fb->integerParameter('orderId'));
				$qb->assign($fb->column('line_count'), $fb->integerParameter('lineCount'));
				$qb->assign($fb->column('total_amount'), $fb->decimalParameter('totalAmount'));
				$qb->assign($fb->column('total_amount_with_taxes'), $fb->decimalParameter('totalAmountWithTaxes'));
				$qb->assign($fb->column('payment_amount_with_taxes'), $fb->decimalParameter('paymentAmountWithTaxes'));
				$qb->assign($fb->column('currency_code'), $fb->parameter('currencyCode'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
						$fb->eq($fb->column('locked'), $fb->booleanParameter('locked'))
					)
				);
				$uq = $qb->updateQuery();

				$uq->bindParameter('lastUpdate', $cart->lastUpdate());
				$uq->bindParameter('cartData', serialize($cart));
				$uq->bindParameter('webStoreId', $cart->getWebStoreId());
				$uq->bindParameter('ownerId', $cart->getOwnerId());
				$uq->bindParameter('userId', $cart->getUserId());
				$uq->bindParameter('transactionId', $cart->getTransactionId());
				$uq->bindParameter('orderId', $cart->getOrderId());

				$uq->bindParameter('lineCount', count($cart->getLines()));
				$uq->bindParameter('totalAmount', $cart->getLinesAmount());
				$uq->bindParameter('totalAmountWithTaxes', $cart->getLinesAmountWithTaxes());
				$uq->bindParameter('paymentAmountWithTaxes', $cart->getPaymentAmountWithTaxes());
				$uq->bindParameter('currencyCode', $cart->getCurrencyCode());

				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->bindParameter('locked', false);
				$uq->execute();

				$this->cachedCarts = array();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetCartByIdentifier(\Change\Events\Event $event)
	{
		$identifier = $event->getParam('cartIdentifier');
		if (!$identifier)
		{
			return;
		}

		if (!array_key_exists($identifier, $this->cachedCarts))
		{
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewQueryBuilder('loadCart');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('cart_data'), $fb->column('owner_id'), $fb->column('store_id')
					, $fb->column('user_id'), $fb->column('transaction_id'), $fb->column('order_id'),
					$fb->column('locked'), $fb->column('processing'), $fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			}
			$sq = $qb->query();
			$sq->bindParameter('identifier', $identifier);

			$cartInfo = $sq->getFirstResult($sq->getRowsConverter()
				->addLobCol('cart_data')
				->addIntCol('owner_id', 'store_id', 'user_id', 'transaction_id', 'order_id')
				->addBoolCol('locked', 'processing')->addDtCol('last_update'));

			$this->cachedCarts[$identifier] = $cartInfo;
		}
		else
		{
			$cartInfo = $this->cachedCarts[$identifier];
		}

		if ($cartInfo)
		{
			$cart = unserialize($cartInfo['cart_data']);
			if ($cart instanceof Cart)
			{
				$cart->setCartManager($this);
				$cart->setIdentifier($identifier)
					->setWebStoreId($cartInfo['store_id'])
					->setUserId($cartInfo['user_id'])
					->setLocked($cartInfo['locked'])
					->setProcessing($cartInfo['processing'])
					->setOwnerId($cartInfo['owner_id'])
					->setTransactionId($cartInfo['transaction_id'])
					->setOrderId($cartInfo['order_id']);
				$cart->lastUpdate($cartInfo['last_update']);
				$event->setParam('cart', $cart);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultMergeCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$cartToMerge = $event->getParam('cartToMerge');
		if (($cart instanceof \Rbs\Commerce\Cart\Cart) && ($cartToMerge instanceof \Rbs\Commerce\Cart\Cart))
		{
			if ($cart->getWebStoreId() == $cartToMerge->getWebStoreId())
			{
				foreach ($cartToMerge->getLines() as $lineToMerge)
				{
					$currentCartLine = $cart->getLineByKey($lineToMerge->getKey());
					if ($currentCartLine === null)
					{
						$this->addLine($cart, $lineToMerge->toArray());
					}
				}
			}
		}
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

		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart));
		$this->getEventManager()->trigger('getUnlockedCart', $this, $args);
		if (isset($args['newCart']) && $args['newCart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			return $args['newCart'];
		}
		throw new \RuntimeException('Unable to get a new cart', 999999);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetUnlockedCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			/** @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
			$newCart = $this->getNewCart($webStore, $cart->getBillingArea(), $cart->getZone());
			$newCart->getContext()->set('lockedCart', $cart->getIdentifier());
			$newCart->setUserId($cart->getUserId());
			$newCart->setOwnerId($cart->getOwnerId());
			foreach ($cart->getLines() as $line)
			{
				$this->addLine($newCart, $newCart->getNewLine($line->toArray()));
			}
			$newCart->setEmail($cart->getEmail());
			$newCart->setAddress($cart->getAddress());
			$newCart->setShippingModes($cart->getShippingModes());
			foreach ($cart->getCoupons() as $coupon)
			{
				$newCart->appendCoupon($coupon);
			}
			$event->setParam('newCart', $newCart);
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
				elseif ($line->getUnitAmount() === null)
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
				$commerceServices->getStockManager()->cleanupReservations($cart->getIdentifier());
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultLockCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$lastUpdate = new \DateTime();

			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
				$qb->assign($fb->column('owner_id'), $fb->integerParameter('ownerId'));
				$qb->assign($fb->column('locked'), $fb->booleanParameter('locked'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
						$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
					)
				);

				$uq = $qb->updateQuery();
				$uq->bindParameter('lastUpdate', $lastUpdate);
				$uq->bindParameter('ownerId', $cart->getOwnerId());
				$uq->bindParameter('locked', true);
				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->bindParameter('whereLocked', false);
				$uq->execute();

				$tm->commit();
				$this->cachedCarts = [];
				$cart->lastUpdate($lastUpdate);
				$cart->setLocked(true);
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultStartProcessingCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$lastUpdate = new \DateTime();

			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
				$qb->assign($fb->column('processing'), $fb->booleanParameter('processing'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
						$fb->eq($fb->column('processing'), $fb->booleanParameter('whereProcessing'))
					)
				);

				$uq = $qb->updateQuery();
				$uq->bindParameter('lastUpdate', $lastUpdate);
				$uq->bindParameter('processing', true);
				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->bindParameter('whereProcessing', false);
				$uq->execute();

				$tm->commit();

				$this->cachedCarts = [];
				$cart->lastUpdate($lastUpdate);
				$cart->setProcessing(true);
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultAffectTransactionId(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$transactionId = $event->getParam('transactionId');

		if ($cart instanceof \Rbs\Commerce\Cart\Cart && is_numeric($transactionId))
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();

				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('transaction_id'), $fb->integerParameter('transactionId'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
						$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
					)
				);

				$uq = $qb->updateQuery();
				$uq->bindParameter('transactionId', $transactionId);
				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->bindParameter('whereLocked', true);
				$uq->execute();

				$tm->commit();
				$this->cachedCarts = [];
				$cart->setTransactionId($transactionId);
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultAffectOrder(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$order = $event->getParam('order');

		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $order)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();
				$orderId = ($order instanceof \Change\Documents\AbstractDocument) ? $order->getId() : intval($order);
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('order_id'), $fb->integerParameter('order_id'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
						$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
					)
				);

				$uq = $qb->updateQuery();
				$uq->bindParameter('order_id', $orderId);
				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->bindParameter('whereLocked', true);
				$uq->execute();

				$tm->commit();
				$this->cachedCarts = [];
				$cart->setOrderId($orderId);
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
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
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultAffectUser(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$user = $event->getParam('user');

		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $user)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();
				$userId = ($user instanceof \Change\Documents\AbstractDocument) ? $user->getId() : intval($user);
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_commerce_dat_cart'));
				$qb->assign($fb->column('user_id'), $fb->integerParameter('user_id'));
				$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));

				$uq = $qb->updateQuery();
				$uq->bindParameter('user_id', $userId);
				$uq->bindParameter('identifier', $cart->getIdentifier());
				$uq->execute();

				$tm->commit();
				$this->cachedCarts = [];
				$cart->setUserId($userId);
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param bool $purge
	 */
	public function deleteCart(\Rbs\Commerce\Cart\Cart $cart, $purge = false)
	{
		try
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'purge' => $purge));
			$this->getEventManager()->trigger('deleteCart', $this, $args);
		}
		catch (\Exception $e)
		{
			$this->getLogging()->exception($e);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultDeleteCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$purge = $event->getParam('purge', false);

		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			try
			{
				$tm->begin();

				$this->getStockManager()->cleanupReservations($cart->getIdentifier());

				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_commerce_dat_cart'));
				if ($purge)
				{
					$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
				}
				else
				{
					$qb->where(
						$fb->logicAnd(
							$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
							$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
						)
					);
				}

				$uq = $qb->deleteQuery();
				$uq->bindParameter('identifier', $cart->getIdentifier());
				if (!$purge) {
					$uq->bindParameter('whereLocked', false);
				}
				$uq->execute();


				$tm->commit();
				$this->cachedCarts = [];
				$cart->getContext()->set('storageId', null);


			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
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
			$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
			if ($webStore instanceof \Rbs\Store\Documents\Webstore)
			{
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			foreach ($cart->getLines() as $index => $line)
			{
				$line->setIndex($index);
				$this->refreshCartLine($cart, $line);
			}

			$this->refreshLinesAmount($cart);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeModifiers(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{

			$cart->removeAllFees();
			$cart->removeAllDiscounts();

			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$priceManager = $commerceServices->getPriceManager();
			$this->buildTotalAmount($cart, $priceManager);

			$processManager = $commerceServices->getProcessManager();
			$process = $processManager->getOrderProcessByCart($cart);
			if ($process)
			{
				$coupons = $cart->removeAllCoupons();
				foreach ($coupons as $oldCoupon)
				{
					$couponCode = $oldCoupon->getCode();
					$q = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Discount_Coupon');
					$q->andPredicates($q->activated(), $q->eq('code', $couponCode), $q->eq('orderProcess', $process));

					/** @var $couponDocument \Rbs\Discount\Documents\Coupon */
					foreach ($q->getDocuments() as $couponDocument)
					{
						if ($couponDocument->isCompatibleWith($cart))
						{
							$coupon = new \Rbs\Commerce\Process\BaseCoupon();
							$coupon->setCode($couponCode);
							$coupon->setTitle($couponDocument->getCurrentLocalization()->getTitle());
							$coupon->getOptions()->set('id', $couponDocument->getId());
							$cart->appendCoupon($coupon);
							break;
						}
					}
				}

				$documents = $process->getAvailableModifiers();
				foreach ($documents as $document)
				{
					if ($document instanceof \Rbs\Commerce\Documents\Fee) {
						$modifier = $document->getValidModifier($cart);
						if ($modifier) {
							$modifier->apply();
							$this->buildTotalAmount($cart, $priceManager);
						}
					}
					elseif ($document instanceof \Rbs\Discount\Documents\Discount) {
						$modifier = $document->getValidModifier($cart);
						if ($modifier) {
							$modifier->apply();
							$this->buildTotalAmount($cart, $priceManager);
						}
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	protected function buildTotalAmount($cart, $priceManager)
	{
		//Add fees and discounts
		$totalAmount = $cart->getLinesAmount();
		$totalAmountWithTaxes = $cart->getLinesAmountWithTaxes();
		$totalTaxes = $cart->getLinesTaxes();
		foreach ($cart->getFees() as $fee)
		{
			$totalAmount += $fee->getAmount();
			$totalAmountWithTaxes += $fee->getAmountWithTaxes();
			$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $fee->getTaxes());
		}

		foreach ($cart->getDiscounts() as $discount)
		{
			$totalAmount += $discount->getAmount();
			$totalAmountWithTaxes += $discount->getAmountWithTaxes();
			$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $discount->getTaxes());
		}

		$cart->setTotalAmount($totalAmount);
		$cart->setTotalAmountWithTaxes($totalAmountWithTaxes);
		$cart->setTotalTaxes($totalTaxes);

		//Add Credit notes
		$paymentAmount = $totalAmountWithTaxes;
		foreach ($cart->getCreditNotes() as $creditNote)
		{
			$paymentAmount += $creditNote->getAmount();
		}

		$cart->setPaymentAmountWithTaxes($paymentAmount);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\CartLine $line
	 */
	protected function refreshCartLine(\Rbs\Commerce\Cart\Cart $cart, \Rbs\Commerce\Cart\CartLine $line)
	{
		$webStore = $this->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
		$billingArea = $cart->getBillingArea();
		if (!($webStore instanceof \Rbs\Store\Documents\Webstore) || !$billingArea)
		{
			return;
		}

		$pricesValueWithTax = $cart->getPricesValueWithTax();
		foreach ($line->getItems() as $item)
		{
			if (!$item->getOptions()->get('lockedPrice', false))
			{
				$sku = $this->getStockManager()->getSkuByCode($item->getCodeSKU());
				if ($sku)
				{
					$price = $this->getPriceManager()->getPriceBySku($sku,
						['webStore' => $webStore, 'billingArea' => $billingArea, 'cart' => $cart, 'cartLine' => $line]);
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
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	protected function refreshLinesAmount($cart)
	{
		$priceManager = $this->getPriceManager();

		/* @var $linesTaxes \Rbs\Price\Tax\TaxApplication[] */
		$linesTaxes = [];
		$linesAmount = 0.0;
		$linesAmountWithTaxes = 0.0;

		$currencyCode = $cart->getCurrencyCode();
		$zone = $cart->getZone();
		$taxes = $cart->getTaxes();
		if (!$currencyCode || !$zone || count($taxes) == 0)
		{
			$taxes = null;
		}

		foreach ($cart->getLines() as $line)
		{
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
			$linesAmount += $amount;
			$linesAmountWithTaxes += $amountWithTaxes;
			$linesTaxes = $priceManager->addTaxesApplication($linesTaxes, $lineTaxes);
		}

		$cart->setLinesTaxes($linesTaxes);
		$cart->setLinesAmount($linesAmount);
		$cart->setLinesAmountWithTaxes($linesAmountWithTaxes);
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
		return isset($args['filtersDefinition']) && is_array($args['filtersDefinition']) ? array_values($args['filtersDefinition']) : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFiltersDefinition($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$filtersDefinition = $event->getParam('filtersDefinition');
		$defaultsDefinitions = json_decode(file_get_contents(__DIR__ . '/Assets/filtersDefinition.json'), true);
		foreach ($defaultsDefinitions as $definition)
		{
			$definition['config']['group'] = $i18nManager->trans($definition['config']['group'], ['ucf']);
			$definition['config']['listLabel'] = $i18nManager->trans($definition['config']['listLabel'], ['ucf']);
			$definition['config']['label'] = $i18nManager->trans($definition['config']['label'], ['ucf']);
			$filtersDefinition[] = $definition;
		}
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
		$filter = $event->getParam('filter');
		switch ($name)
		{
			case 'linesPriceValue':
			case 'totalPriceValue':
				if (isset($filter['parameters']) && is_array($filter['parameters']))
				{
					$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
					$expected = $parameters['value'];
					$operator = $parameters['operator'];

					/** @var $cart \Rbs\Commerce\Cart\Cart */
					$cart = $event->getParam('cart');
					if ($name === 'linesPriceValue')
					{
						$value = $cart->getPricesValueWithTax() ? $cart->getLinesAmountWithTaxes() : $cart->getLinesAmount();
					}
					else
					{
						$value = $cart->getPricesValueWithTax() ? $cart->getTotalAmountWithTaxes() : $cart->getTotalAmount();
					}
					$event->setParam('valid', $this->testNumValue($value, $operator, $expected));
				}
				break;
			case 'hasCoupon':
				if (isset($filter['parameters']) && is_array($filter['parameters']))
				{
					$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
					$expected = $parameters['value'];
					$operator = $parameters['operator'];
					$valid = null;

					/** @var $cart \Rbs\Commerce\Cart\Cart */
					$cart = $event->getParam('cart');
					if ($operator === 'isNull')
					{
						$valid = (count($cart->getCoupons()) === 0);
					}
					elseif ($operator === 'eq')
					{
						$valid = false;
						foreach ($cart->getCoupons() as $coupon)
						{
							if ($coupon->getOptions()->get('id') == $expected)
							{
								$valid = true;
								break;
							}
						}
					}
					elseif ($operator === 'neq')
					{
						$valid = true;
						foreach ($cart->getCoupons() as $coupon)
						{
							if ($coupon->getOptions()->get('id') == $expected)
							{
								$valid = false;
								break;
							}
						}
					}

					if ($valid !== null)
					{
						$event->setParam('valid', $valid);
					}
				}
				break;
		}
	}

	/**
	 * @param $value
	 * @param $operator
	 * @param $expeted
	 * @return boolean
	 */
	protected function testNumValue($value, $operator, $expeted)
	{
		switch ($operator)
		{
			case 'eq':
				return abs($value - $expeted) < 0.0001;
			case 'neq':
				return abs($value - $expeted) > 0.0001;
			case 'lte':
				return $value <= $expeted;
			case 'gte':
				return $value >= $expeted;
			case 'isNull':
				return $value === null;
		}
		return false;
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

	/**
	 * @param mixed $value
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function getSerializableValue($value)
	{
		if ($value instanceof \Change\Documents\AbstractDocument)
		{
			return new \Change\Documents\DocumentWeakReference($value);
		}
		elseif (is_array($value) || $value instanceof \Zend\Stdlib\Parameters)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->getSerializableValue($v);
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @throws \RuntimeException
	 * @return array|\Change\Documents\AbstractDocument|mixed
	 */
	public function restoreSerializableValue($value)
	{
		if ($value instanceof \Change\Documents\DocumentWeakReference)
		{
			return $value->getDocument($this->getDocumentManager());
		}
		elseif (is_array($value) || $value instanceof \Zend\Stdlib\Parameters)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->restoreSerializableValue($v);
			}
		}
		return $value;
	}

	/**
	 * @return integer
	 */
	public function getCleanupTTL()
	{
		return $this->getConfiguration()->getEntry('Rbs/Commerce/Cart/CleanupTTL', 60 * 60); //60 minutes
	}

}
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
		$eventManager->attach('getNewCart', [$this, 'onDefaultGetNewCart'], 15);
		$eventManager->attach('getNewCart', [$this, 'onDefaultInitNewCart'], 10);
		$eventManager->attach('getNewCart', [$this, 'onDefaultSetCartUserContext'], 5);

		$eventManager->attach('getCartByIdentifier', [$this, 'onDefaultGetCartByIdentifier'], 10);
		$eventManager->attach('getCartByIdentifier', [$this, 'onDefaultSetCartUserContext'], 5);

		$eventManager->attach('saveCart', [$this, 'onDefaultSaveCart'], 5);

		$eventManager->attach('getLastCartIdentifier', [$this, 'onDefaultGetLastCartIdentifier'], 5);
		$eventManager->attach('mergeCart', [$this, 'onDefaultMergeCart'], 5);
		$eventManager->attach('getUnlockedCart', [$this, 'onDefaultGetUnlockedCart'], 5);
		$eventManager->attach('cloneCartContentForUser', [$this, 'onDefaultCloneCartContentForUser'], 5);
		$eventManager->attach('lockCart', [$this, 'onDefaultLockCart'], 5);
		$eventManager->attach('startProcessingCart', [$this, 'onDefaultStartProcessingCart'], 5);
		$eventManager->attach('affectTransactionId', [$this, 'onDefaultAffectTransactionId'], 5);
		$eventManager->attach('affectUser', [$this, 'onDefaultAffectUser'], 5);
		$eventManager->attach('deleteCart', [$this, 'onDefaultDeleteCart'], 5);
		$eventManager->attach('getProcessingCartsByUser', [$this, 'onDefaultGetProcessingCartsByUser'], 5);
		$eventManager->attach('getCartData', [$this, 'onDefaultGetCartData'], 5);
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @param array $context : user
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function getNewCart($webStore = null, $billingArea = null, $zone = null, array $context = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['webStore' => $webStore, 'billingArea' => $billingArea, 'zone' => $zone,
			'context' => $context]);
		$this->getEventManager()->trigger('getNewCart', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			return $args['cart'];
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

			$this->cachedCarts = [];
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('cart', $cart);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultInitNewCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$webStore = $event->getParam('webStore');
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$cart->setWebStoreId($webStore->getId());
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			$billingArea = $event->getParam('billingArea');
			if ($billingArea instanceof \Rbs\Price\Tax\BillingAreaInterface)
			{
				$cart->setBillingArea($billingArea);
				$zone = $event->getParam('zone');
				$cart->setZone($zone);
			}

			$context = $event->getParam('context');
			if (is_array($context) && count($context))
			{
				$user = isset($context['user']) ? $context['user'] : null;
				if ($user instanceof \Change\User\UserInterface)
				{
					$cart->setUserId($user->authenticated() ? $user->getId() : 0);
					unset($context['user']);
				}

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
			}

			$cart->getContext()->set('LCID', $this->getDocumentManager()->getLCID());
		}
	}
	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 */
	public function saveCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		if (!$cart->isLocked())
		{
			$this->validCart($cart, 'save');
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
				$qb->assign($fb->column('line_count'), $fb->integerParameter('lineCount'));
				$qb->assign($fb->column('total_amount_without_taxes'), $fb->decimalParameter('totalAmountWithoutTaxes'));
				$qb->assign($fb->column('total_amount_with_taxes'), $fb->decimalParameter('totalAmountWithTaxes'));
				$qb->assign($fb->column('payment_amount'), $fb->decimalParameter('paymentAmount'));
				$qb->assign($fb->column('currency_code'), $fb->parameter('currencyCode'));
				$qb->assign($fb->column('email'), $fb->parameter('email'));
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

				$uq->bindParameter('lineCount', count($cart->getLines()));
				$uq->bindParameter('totalAmountWithoutTaxes', $cart->getTotalAmountWithoutTaxes());
				$uq->bindParameter('totalAmountWithTaxes', $cart->getLinesAmountWithTaxes());
				$uq->bindParameter('paymentAmount', $cart->getPaymentAmount());
				$uq->bindParameter('currencyCode', $cart->getCurrencyCode());
				$uq->bindParameter('email', $cart->getEmail());

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
	 * @param \Change\User\UserInterface|integer $user
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array $options
	 * @return string|null
	 */
	public function getLastCartIdentifier($user, $webStore, array $options = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['user' => $user, 'webStore' => $webStore, 'options' => $options]);
		$em->trigger('getLastCartIdentifier', $this, $args);
		if (isset($args['cartIdentifier']) && is_string($args['cartIdentifier']))
		{
			return $args['cartIdentifier'];
		}
		return null;
	}


	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetLastCartIdentifier(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		if ($user instanceof \Change\User\UserInterface) {
			$user = $user->getId();
		}
		if (!is_numeric($user) || $user <= 0) {
			return;
		}

		$webStore = $event->getParam('webStore');
		if ($webStore instanceof \Rbs\Store\Documents\WebStore) {
			$webStore = $webStore->getId();
		}
		if (!is_numeric($webStore)) {
			return;
		}

		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('getLastCartIdentifier');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'), $fb->column('owner_id'), $fb->column('store_id')
				, $fb->column('user_id'), $fb->column('transaction_id'),
				$fb->column('locked'), $fb->column('processing'), $fb->column('last_update'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('user_id'), $fb->integerParameter('userId')),
					$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing'))

				));
			$qb->orderDesc($fb->column('last_update'));
		}
		$sq = $qb->query();
		$sq->bindParameter('userId', $user);
		$sq->bindParameter('storeId', $webStore);
		$sq->bindParameter('processing', false);
		$cartIdentifier = $sq->getFirstResult($sq->getRowsConverter()
			->addStrCol('identifier')->singleColumn('identifier'));
		$event->setParam('cartIdentifier', $cartIdentifier);
	}

	/**
	 * @param string $cartIdentifier
	 * @return \Rbs\Commerce\Cart\Cart|null
	 */
	public function getCartByIdentifier($cartIdentifier)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cartIdentifier' => $cartIdentifier));
		$em->trigger('getCartByIdentifier', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Cart\Cart)
		{
			return $args['cart'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
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
					, $fb->column('user_id'), $fb->column('transaction_id'),
					$fb->column('locked'), $fb->column('processing'), $fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			}
			$sq = $qb->query();
			$sq->bindParameter('identifier', $identifier);

			$cartInfo = $sq->getFirstResult($sq->getRowsConverter()
				->addLobCol('cart_data')
				->addIntCol('owner_id', 'store_id', 'user_id', 'transaction_id')
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
					->setTransactionId($cartInfo['transaction_id']);
				$cart->lastUpdate($cartInfo['last_update']);
				$event->setParam('cart', $cart);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultSetCartUserContext(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
			if ($user->authenticated())
			{
				if ($user->getId() == $cart->getUserId())
				{
					$commerceServices = $event->getServices('commerceServices');
					if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
					{
						$cart->setPriceTargetIds($commerceServices->getContext()->getPriceTargetIds());
					}
				}
			}
			else if ($cart->getUserId() == 0)
			{
				$cart->setPriceTargetIds([]);
			}
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 *     - billingAreaId
	 *     - webStoreId
	 *     - zone
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart|string $cart
	 * @param array $context
	 * @return array
	 */
	public function getCartData($cart, array $context)
	{
		$em = $this->getEventManager();
		if (is_string($cart))
		{
			$cart = $this->getCartByIdentifier($cart);
		}

		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$eventArgs = $em->prepareArgs(['cart' => $cart, 'context' => $context]);
			$em->trigger('getCartData', $this, $eventArgs);
			if (isset($eventArgs['cartData']))
			{
				$productData = $eventArgs['cartData'];
				if (is_object($productData))
				{
					$callable = [$productData, 'toArray'];
					if (is_callable($callable))
					{
						$productData = call_user_func($callable);
					}
				}
				if (is_array($productData))
				{
					return $productData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: cart, context
	 * Output param: cartData
	 * @param \Change\Events\Event $event
	 */
	public function  onDefaultGetCartData(\Change\Events\Event $event)
	{
		if (!$event->getParam('cartData'))
		{
			$cartDataComposer = new \Rbs\Commerce\Cart\CartDataComposer($event);
			$event->setParam('cartData', $cartDataComposer->toArray());
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
			$newCart->setPriceTargetIds($cart->getPriceTargetIds());
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

			// Transfer reservations
			$this->getStockManager()->transferReservations($cart->getIdentifier(), $newCart->getIdentifier());

			$event->setParam('newCart', $newCart);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Change\User\UserInterface $user
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function cloneCartContentForUser($cart, $user)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart, 'user' => $user));
		$this->getEventManager()->trigger('cloneCartContentForUser', $this, $args);
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
	public function onDefaultCloneCartContentForUser(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$user = $event->getParam('user');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $user instanceof \Change\User\UserInterface)
		{
			/** @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
			$newCart = $this->getNewCart($webStore, $cart->getBillingArea(), $cart->getZone(), ['user' => $user]);
			$newCart->getContext()->set('fromCart', $cart->getIdentifier());
			foreach ($cart->getLines() as $line)
			{
				$this->addLine($newCart, $newCart->getNewLine($line->toArray()));
			}

			// Transfer reservations
			$this->getStockManager()->transferReservations($cart->getIdentifier(), $newCart->getIdentifier());
			$event->setParam('newCart', $newCart);
		}
	}



	/**
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string $for [save] | lock
	 * @return boolean
	 */
	public function validCart(\Rbs\Commerce\Cart\Cart $cart, $for = 'save')
	{
		try
		{
			$cart->setErrors(array());

			$em = $this->getEventManager();
			$args = $em->prepareArgs(['cart' => $cart, 'errors' => new \ArrayObject(), 'for' => $for]);

			$em->trigger('validCart', $this, $args);
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
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return boolean
	 */
	public function lockCart(\Rbs\Commerce\Cart\Cart $cart)
	{
		if (!$cart->isLocked())
		{
			try
			{
				if ($this->validCart($cart, 'lock'))
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
			$cartIdentifier = $cart->getIdentifier();
			$tm = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			try
			{
				$tm->begin();

				$removedCreditNotes = $cart->removeAllCreditNotes();
				foreach ($removedCreditNotes as $removedCreditNote)
				{
					$creditNoteDocument = $documentManager->getDocumentInstance($removedCreditNote->getId());
					if ($creditNoteDocument instanceof \Rbs\Order\Documents\CreditNote)
					{
						$creditNoteDocument->removeUsageByTargetIdentifier($cartIdentifier);
						$creditNoteDocument->save();
					}
				}

				$this->getStockManager()->cleanupReservations($cartIdentifier);

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
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	public function buildTotalAmount(\Rbs\Commerce\Cart\Cart $cart, \Rbs\Price\PriceManager $priceManager)
	{
		$currencyCode =  $cart->getCurrencyCode();
		if (!$currencyCode) {
			return;
		}
		$precision = $priceManager->getRoundPrecisionByCurrencyCode($currencyCode);

		//Lines Amount
		$this->refreshLinesAmount($cart, $priceManager);
		$zone = $cart->getZone();
		$priceWithTax = $cart->getPricesValueWithTax();

		//Add fees and discounts
		$totalAmountWithoutTaxes = $cart->getLinesAmountWithoutTaxes();
		$totalAmountWithTaxes = $cart->getLinesAmountWithTaxes();
		$totalTaxes = $zone ? $cart->getLinesTaxes() : null;
		foreach ($cart->getFees() as $fee)
		{
			if ($zone)
			{
				$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $fee->getTaxes());
				$totalAmountWithoutTaxes += $fee->getAmountWithoutTaxes();
				$totalAmountWithTaxes += $fee->getAmountWithTaxes();
			}
			else
			{
				if ($priceWithTax)
				{
					$totalAmountWithTaxes += $fee->getAmountWithTaxes();
				}
				else
				{
					$totalAmountWithoutTaxes += $fee->getAmountWithoutTaxes();
				}
			}
		}

		foreach ($cart->getDiscounts() as $discount)
		{
			if ($zone)
			{
				$totalAmountWithoutTaxes += $discount->getAmountWithoutTaxes();
				$totalAmountWithTaxes += $discount->getAmountWithTaxes();
				$totalTaxes = $priceManager->addTaxesApplication($totalTaxes, $discount->getTaxes());
			}
			else
			{
				if ($priceWithTax)
				{
					$totalAmountWithTaxes += $discount->getAmountWithTaxes();
				}
				else
				{
					$totalAmountWithoutTaxes += $discount->getAmountWithoutTaxes();
				}
			}
		}

		$totalAmountWithoutTaxes = $priceManager->roundValue($totalAmountWithoutTaxes, $precision);
		$totalAmountWithTaxes = $priceManager->roundValue($totalAmountWithTaxes, $precision);

		$cart->setTotalAmountWithoutTaxes($totalAmountWithoutTaxes);
		$cart->setTotalAmountWithTaxes($totalAmountWithTaxes);

		if ($zone)
		{
			foreach ($totalTaxes as $tax)
			{
				$tax->setValue($priceManager->roundValue($tax->getValue(), $precision));
			}
			$cart->setTotalTaxes($totalTaxes);
		}
		else
		{
			$cart->setTotalTaxes([]);
		}

		//Add Credit notes
		$paymentAmount = ($zone || $priceWithTax) ? $totalAmountWithTaxes : $totalAmountWithoutTaxes;

		foreach ($cart->getCreditNotes() as $creditNote)
		{
			$paymentAmount += $priceManager->roundValue($creditNote->getAmount(), $precision);
		}

		$cart->setPaymentAmount($paymentAmount);
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\CartLine $line
	 */
	public function refreshCartLine(\Rbs\Commerce\Cart\Cart $cart, \Rbs\Commerce\Cart\CartLine $line)
	{
		$webStore = $this->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
		$billingArea = $cart->getBillingArea();
		if (!($webStore instanceof \Rbs\Store\Documents\Webstore) || !$billingArea)
		{
			return;
		}
		$options = ['webStore' => $webStore, 'billingArea' => $billingArea, 'targetIds' => $cart->getPriceTargetIds(),
			'cart' => $cart, 'line' => $line];
		$pricesValueWithTax = $cart->getPricesValueWithTax();
		foreach ($line->getItems() as $item)
		{
			if (!$item->getOptions()->get('lockedPrice', false))
			{
				$sku = $this->getStockManager()->getSkuByCode($item->getCodeSKU());
				if ($sku)
				{
					$options['lineItem'] = $item;
					$price = $this->getPriceManager()->getPriceBySku($sku, $options);
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
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	protected function refreshLinesAmount($cart, $priceManager)
	{
		$currencyCode = $cart->getCurrencyCode();
		if (!$currencyCode) {
			return;
		}
		$precision = $priceManager->getRoundPrecisionByCurrencyCode($currencyCode);
		$zone = $cart->getZone();
		$pricesValueWithTax = $cart->getPricesValueWithTax();
		if ($zone)
		{
			/* @var $linesTaxes \Rbs\Price\Tax\TaxApplication[] */
			$linesTaxes = [];
			$linesAmountWithoutTaxes = 0.0;
			$linesAmountWithTaxes = 0.0;
		}
		else
		{
			$linesTaxes = null;
			$linesAmountWithTaxes = $pricesValueWithTax ? 0.0 : null;
			$linesAmountWithoutTaxes = $pricesValueWithTax ? null : 0.0;
		}

		$taxes = $cart->getTaxes();
		foreach ($cart->getLines() as $line)
		{
			$lineTaxes = $zone ? [] : null;

			$amountWithoutTaxes = null;
			$amountWithTaxes = null;

			$basedAmountWithoutTaxes = null;
			$basedAmountWithTaxes = null;

			$lineQuantity = $line->getQuantity();
			if ($lineQuantity)
			{
				foreach ($line->getItems() as $item)
				{
					$price = $item->getPrice();
					if ($price && (($value = $price->getValue()) !== null))
					{
						$lineItemValue = $value * $lineQuantity;
						if ($price->getBasePriceValue() !== null)
						{
							$lineItemBasedValue = $price->getBasePriceValue() * $lineQuantity;
						}
						else
						{
							$lineItemBasedValue = null;
						}

						if ($zone)
						{
							$taxArray = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $lineQuantity);
							$basedAmountTaxArray = [];
							if (count($taxArray))
							{
								$lineTaxes = $priceManager->addTaxesApplication($lineTaxes, $taxArray);
								if ($lineItemBasedValue)
								{
									$rate = $lineItemBasedValue /$lineItemValue;
									foreach ($taxArray as $tax)
									{
										$basedAmountTax = clone($tax);
										$basedAmountTax->setValue($tax->getValue() * $rate);
										$basedAmountTaxArray[] = $basedAmountTax;
									}
								}
							}

							if ($pricesValueWithTax)
							{
								$amountWithTaxes += $lineItemValue;
								$amountWithoutTaxes += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);

								$basedAmountWithTaxes += $lineItemBasedValue;
								$basedAmountWithoutTaxes += $priceManager->getValueWithoutTax($lineItemBasedValue, $basedAmountTaxArray);
							}
							else
							{
								$amountWithoutTaxes += $lineItemValue;
								$amountWithTaxes = $priceManager->getValueWithTax($lineItemValue, $taxArray);
								$basedAmountWithoutTaxes += $lineItemBasedValue;
								$basedAmountWithTaxes += $priceManager->getValueWithTax($lineItemBasedValue, $basedAmountTaxArray);
							}
						}
						else
						{
							if ($pricesValueWithTax)
							{
								$amountWithTaxes += $lineItemValue;
								$basedAmountWithTaxes += $lineItemBasedValue;
							}
							else
							{
								$amountWithoutTaxes += $lineItemValue;
								$basedAmountWithoutTaxes += $lineItemBasedValue;
							}
						}
					}
				}
			}

			$line->setTaxes($lineTaxes);
			$amountWithTaxes = $priceManager->roundValue($amountWithTaxes, $precision);
			if ($amountWithTaxes !== null)
			{
				$linesAmountWithTaxes += $amountWithTaxes;
			}
			$amountWithoutTaxes = $priceManager->roundValue($amountWithoutTaxes, $precision);
			if ($amountWithoutTaxes !== null)
			{
				$linesAmountWithoutTaxes += $amountWithoutTaxes;
			}
			$line->setAmountWithTaxes($amountWithTaxes);
			$line->setAmountWithoutTaxes($amountWithoutTaxes);

			$line->setBasedAmountWithTaxes($priceManager->roundValue($basedAmountWithTaxes, $precision));
			$line->setBasedAmountWithoutTaxes($priceManager->roundValue($basedAmountWithoutTaxes, $precision));
			if ($lineTaxes !== null)
			{
				$linesTaxes = $priceManager->addTaxesApplication($linesTaxes, $lineTaxes);
			}
		}

		if (is_array($linesTaxes)) {
			$cart->setLinesTaxes($linesTaxes);
		}
		$cart->setLinesAmountWithoutTaxes($linesAmountWithoutTaxes);
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

	/**
	 * @param \Rbs\User\Documents\User $user
	 * @return \Rbs\Commerce\Cart\Cart[]
	 */
	public function getProcessingCartsByUser($user)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('user' => $user));
		$this->getEventManager()->trigger('getProcessingCartsByUser', $this, $args);
		if (isset($args['carts']))
		{
			return $args['carts'];
		}
		return array();
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetProcessingCartsByUser(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		if ($user instanceof \Rbs\User\Documents\User)
		{
			$carts = array();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewQueryBuilder('onDefaultGetCartsWithTransactionByUser');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('identifier'), $fb->column('cart_data'), $fb->column('owner_id'), $fb->column('store_id'),
					$fb->column('user_id'), $fb->column('transaction_id'), $fb->column('locked'),
					$fb->column('processing'), $fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				$qb->where($fb->logicAnd(
					$fb->eq($fb->column('processing'), $fb->parameter('processing')),
					$fb->logicOr(
						$fb->eq($fb->column('user_id'), $fb->parameter('user_id')),
						$fb->eq($fb->column('owner_id'), $fb->parameter('owner_id'))
					)
				));
			}
			$sq = $qb->query();
			$sq->bindParameter('user_id', $user->getId());
			$sq->bindParameter('owner_id', $user->getId());
			$sq->bindParameter('processing', true);

			$converter = $sq->getRowsConverter()
				->addLobCol('cart_data')->addStrCol('identifier')
				->addIntCol('owner_id', 'store_id', 'user_id', 'transaction_id')
				->addBoolCol('locked', 'processing')->addDtCol('last_update');
			foreach ($sq->getResults($converter) as $cartInfo)
			{
				$cart = unserialize($cartInfo['cart_data']);
				if ($cart instanceof Cart)
				{
					$cart->setCartManager($this);
					$cart->setIdentifier($cartInfo['identifier'])
						->setWebStoreId($cartInfo['store_id'])
						->setUserId($cartInfo['user_id'])
						->setLocked($cartInfo['locked'])
						->setProcessing($cartInfo['processing'])
						->setOwnerId($cartInfo['owner_id'])
						->setTransactionId($cartInfo['transaction_id']);
					$cart->lastUpdate($cartInfo['last_update']);
					$carts[] = $cart;
				}
			}
			$event->setParam('carts', $carts);
		}
	}
}
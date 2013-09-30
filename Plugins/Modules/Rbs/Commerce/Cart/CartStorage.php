<?php
namespace Rbs\Commerce\Cart;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentWeakReference;
use Rbs\Commerce\Interfaces\BillingArea;
use Rbs\Commerce\Services\CommerceServices;

/**
 * @name \Rbs\Commerce\Cart\CartStorage
 */
class CartStorage
{
	protected $cachedCarts = array();

	/**
	 * @param array $cachedCarts
	 * @return $this
	 */
	public function setCachedCarts(array $cachedCarts)
	{
		$this->cachedCarts = $cachedCarts;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCachedCarts()
	{
		return $this->cachedCarts;
	}

	/**
	 * @param CommerceServices $commerceServices
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param BillingArea $billingArea
	 * @param string $zone
	 * @param array $context
	 * @throws \Exception
	 * @return Cart
	 */
	public function getNewCart(CommerceServices $commerceServices, $webStore = null, $billingArea = null, $zone = null, array $context = array())
	{
		$tm = $commerceServices->getApplicationServices()->getTransactionManager();
		$cart = null;
		try
		{
			$tm->begin();

			$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$date = new \DateTime();

			if (isset($context['ownerId']))
			{
				$ownerId = intval($context['ownerId']);
				unset($context['ownerId']);
			}
			else
			{
				$ownerId = 0;
			}

			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$webStoreId = $webStore->getId();
			}
			elseif ($commerceServices->getWebStore())
			{
				$webStoreId = $commerceServices->getWebStore()->getId();
			}
			else
			{
				$webStoreId = 0;
			}

			$qb->insert($fb->table('rbs_commerce_dat_cart'),
				$fb->column('creation_date'), $fb->column('last_update'),
				$fb->column('owner_id'), $fb->column('store_id'));
			$qb->addValue($fb->dateTimeParameter('creationDate'));
			$qb->addValue($fb->dateTimeParameter('lastUpdate'));
			$qb->addValue($fb->integerParameter('ownerId'));
			$qb->addValue($fb->integerParameter('webStoreId'));

			$iq = $qb->insertQuery();
			$iq->bindParameter('creationDate', $date);
			$iq->bindParameter('lastUpdate', $date);
			$iq->bindParameter('ownerId', $ownerId);
			$iq->bindParameter('webStoreId', $webStoreId);
			$iq->execute();

			$id = $iq->getDbProvider()->getLastInsertId('rbs_commerce_dat_cart');
			$context['storageId'] = $id;

			$identifier = sha1($id . '-' . $date->getTimestamp());
			$cart = new Cart($identifier, $commerceServices);
			$cart->lastUpdate($date);
			$cart->setOwnerId($ownerId)->setWebStoreId($webStoreId);

			if ($billingArea instanceof BillingArea)
			{
				$cart->setBillingArea($billingArea);
			}
			else
			{
				$cart->setBillingArea($commerceServices->getBillingArea());
			}
			if (is_string($zone))
			{
				$cart->setZone($zone);
			}
			else
			{
				$cart->setZone($commerceServices->getZone());
			}
			if (count($context))
			{
				$cart->getContext()->fromArray($context);
			}

			$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('identifier'), $fb->parameter('identifier'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->assign($fb->column('currency_code'), $fb->parameter('currencyCode'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('cartData', serialize($cart));
			$uq->bindParameter('currencyCode', $cart->getCurrencyCode());
			$uq->bindParameter('id', $id);
			$uq->execute();

			$this->cachedCarts = array();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $cart;
	}

	/**
	 * @param string $identifier
	 * @param CommerceServices $commerceServices
	 * @return Cart|null
	 */
	public function loadCart($identifier, CommerceServices $commerceServices)
	{
		if (!array_key_exists($identifier, $this->cachedCarts))
		{
			$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewQueryBuilder('loadCart');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('cart_data'), $fb->column('owner_id'), $fb->column('store_id'),
					$fb->column('locked'), $fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			}
			$sq = $qb->query();
			$sq->bindParameter('identifier', $identifier);

			$cartInfo = $sq->getFirstResult($sq->getRowsConverter()
				->addLobCol('cart_data')
				->addIntCol('owner_id', 'store_id')
				->addBoolCol('locked')->addDtCol('last_update'));

			//$commerceServices->getApplicationServices()->getLogging()->fatal(var_export($cartInfo, true));
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
				$cart->setCommerceServices($commerceServices);
				$cart->setOwnerId($cartInfo['owner_id'])
					->setWebStoreId($cartInfo['store_id'])
					->setIdentifier($identifier)
					->setLocked($cartInfo['locked']);

				$cart->lastUpdate($cartInfo['last_update']);
				return $cart;
			}
		}
		return null;
	}

	/**
	 * @param Cart $cart
	 * @throws \Exception
	 */
	public function saveCart(Cart $cart)
	{
		$cart->lastUpdate(new \DateTime());
		$applicationServices = $cart->getCommerceServices()->getApplicationServices();
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->assign($fb->column('owner_id'), $fb->integerParameter('ownerId'));
			$qb->assign($fb->column('store_id'), $fb->integerParameter('webStoreId'));
			$qb->assign($fb->column('line_count'), $fb->integerParameter('lineCount'));
			$qb->assign($fb->column('price_value'), $fb->decimalParameter('priceValue'));
			$qb->assign($fb->column('price_value_with_tax'), $fb->decimalParameter('priceValueWithTax'));
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
			$uq->bindParameter('ownerId', $cart->getOwnerId());
			$uq->bindParameter('webStoreId', $cart->getWebStoreId());
			$uq->bindParameter('lineCount', count($cart->getLines()));
			$uq->bindParameter('priceValue', $cart->getPriceValue());
			$uq->bindParameter('priceValueWithTax', $cart->getPriceValueWithTax());
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

	/**
	 * @param Cart $cart
	 * @param integer $ownerId
	 * @throws \Exception
	 */
	public function lockCart(Cart $cart, $ownerId = null)
	{
		$cart->lastUpdate(new \DateTime());
		if ($ownerId !== null)
		{
			$cart->setOwnerId($ownerId);
		}

		$applicationServices = $cart->getCommerceServices()->getApplicationServices();
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
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
			$uq->bindParameter('lastUpdate', $cart->lastUpdate());
			$uq->bindParameter('ownerId', $cart->getOwnerId());
			$uq->bindParameter('locked', true);
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', false);
			$uq->execute();

			$this->cachedCarts = array();

			$tm->commit();
			$cart->setLocked(true);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param Cart $cart
	 * @throws \Exception
	 */
	public function deleteCart(Cart $cart)
	{
		$applicationServices = $cart->getCommerceServices()->getApplicationServices();
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_commerce_dat_cart'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
				)
			);
			$uq = $qb->deleteQuery();
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', false);
			$uq->execute();

			$this->cachedCarts = array();
			$tm->commit();
			$cart->setLocked(true);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Interfaces\Cart $cartToMerge
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function mergeCart($cart, $cartToMerge)
	{
		if ($cart->getWebStoreId() == $cartToMerge->getWebStoreId())
		{
			$cartManager = $cart->getCommerceServices()->getCartManager();

			foreach ($cartToMerge->getLines() as $lineToMerge)
			{
				$currentCartLine = $cart->getLineByKey($lineToMerge->getKey());
				if ($currentCartLine === null)
				{
					$config = new CartLineConfig($cart->getCommerceServices(), $lineToMerge->toArray());
					$cartManager->addLine($cart, $config, $lineToMerge->getQuantity());
				}
				else
				{
					$newQuantity = $currentCartLine->getQuantity() + $lineToMerge->getQuantity();
					$cartManager->updateLineQuantityByKey($cart, $currentCartLine->getKey(), $newQuantity);
				}
			}
		}
		return $cart;
	}

	/**
	 * @param string $identifier
	 * @param CommerceServices $commerceServices
	 * @throws \Exception
	 */
	public function purgeCart($identifier, CommerceServices $commerceServices)
	{
		$applicationServices = $commerceServices->getApplicationServices();
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			$uq = $qb->deleteQuery();
			$uq->bindParameter('identifier', $identifier);

			$this->cachedCarts = array();

			$uq->execute();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param mixed $value
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function getSerializableValue($value)
	{
		if ($value instanceof AbstractDocument)
		{
			return new DocumentWeakReference($value);
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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function restoreSerializableValue($value, CommerceServices $commerceServices)
	{
		if ($value instanceof DocumentWeakReference)
		{
			return $value->getDocument($commerceServices->getDocumentServices()->getDocumentManager());
		}
		elseif (is_array($value) || $value instanceof \Zend\Stdlib\Parameters)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->restoreSerializableValue($v, $commerceServices);
			}
		}
		return $value;
	}
}
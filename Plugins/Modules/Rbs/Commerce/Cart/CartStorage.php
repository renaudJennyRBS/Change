<?php
namespace Rbs\Commerce\Cart;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentWeakReference;
use Rbs\Commerce\Services\CommerceServices;

/**
 * @name \Rbs\Commerce\Cart\CartStorage
 */
class CartStorage
{
	/**
	 * @param CommerceServices $commerceServices
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @param string $zone
	 * @param array $context
	 * @throws \Exception
	 * @return Cart
	 */
	public function getNewCart(CommerceServices $commerceServices, $billingArea, $zone, $context)
	{
		$tm = $commerceServices->getApplicationServices()->getTransactionManager();
		$cart = null;
		try
		{
			$tm->begin();

			$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$date = new \DateTime();
			$qb->insert($fb->table('rbs_commerce_dat_cart'), $fb->column('creation_date'), $fb->column('last_update'));
			$qb->addValue($fb->dateTimeParameter('creationDate'));
			$qb->addValue($fb->dateTimeParameter('lastUpdate'));
			$iq = $qb->insertQuery();
			$iq->bindParameter('creationDate', $date);
			$iq->bindParameter('lastUpdate', $date);
			$iq->execute();

			$id = $iq->getDbProvider()->getLastInsertId('rbs_commerce_dat_cart');

			$identifier = sha1($id . '-' . $date->getTimestamp());
			$cart = new Cart($identifier, $commerceServices);
			$cart->lastUpdate($date);
			if ($billingArea instanceof \Rbs\Commerce\Interfaces\BillingArea)
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
			if (is_array($context) && count($context))
			{
				$cart->getContext()->fromArray($context);
			}
			$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('identifier'), $fb->parameter('identifier'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('cartData', serialize($cart));
			$uq->bindParameter('id', $id);
			$uq->execute();

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
		$qb = $commerceServices->getApplicationServices()->getDbProvider()->getNewQueryBuilder('loadCart');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('cart_data'), $fb->column('owner_id'), $fb->column('locked'), $fb->column('last_update'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
		}
		$sq = $qb->query();
		$sq->bindParameter('identifier', $identifier);

		$cartInfo = $sq->getFirstResult($sq->getRowsConverter()->addLobCol('cart_data')
			->addIntCol('owner_id')->addBoolCol('locked')->addDtCol('last_update'));
		if ($cartInfo)
		{
			$cart = unserialize($cartInfo['cart_data']);
			if ($cart instanceof Cart)
			{
				$cart->setCommerceServices($commerceServices);
				$cart->setOwnerId($cartInfo['owner_id'])
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
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('locked', false);
			$uq->execute();

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
			$tm->commit();
			$cart->setLocked(true);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
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
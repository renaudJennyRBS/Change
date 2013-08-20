<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\Cart as CartInterfaces;
use Rbs\Commerce\Services\CommerceServices;

/**
* @name \Rbs\Commerce\Cart\CartStorage
*/
class CartStorage
{
	/**
	 * @param CommerceServices $commerceServices
	 * @throws \Exception
	 * @return Cart
	 */
	public function getNewCart(CommerceServices $commerceServices)
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
			$cart->setBillingArea($commerceServices->getBillingArea());
			$cart->setZone($commerceServices->getZone());

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
			if ($cart instanceof CartInterfaces)
			{
				$cart->setCommerceServices($commerceServices);
				if ($cart instanceof Cart)
				{
					$cart->setOwnerId($cartInfo['owner_id'])
						->setIdentifier($identifier)
						->setLocked($cartInfo['locked']);
					return $cart;
				}
				$cart->lastUpdate($cartInfo['last_update']);
				return $cart;
			}
		}
		return null;
	}

	/**
	 * @param CartInterfaces $cart
	 * @throws \Exception
	 */
	public function saveCart(CartInterfaces $cart)
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
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Job;

/**
 * @name \Rbs\Commerce\Job\CartsCleanup
 */
class CartsCleanup
{
	public function execute(\Change\Job\Event $event)
	{
		$reportedAtSeconds = 20 * 60;
		$logging = $event->getApplication()->getLogging();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$ttl = $commerceServices->getCartManager()->getCleanupTTL();
			$reportedAtSeconds = max(60, intval($ttl / 3));


			$lastUpdate = (new \DateTime())->sub(new \DateInterval('PT' . $ttl . 'S'));

			$dbProvider = $event->getApplicationServices()->getDbProvider();

			//Remove cart
			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->logicAnd(
					$fb->lte($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing')),
					$fb->eq($fb->column('user_id'), $fb->integerParameter('userId'))
				)
			);

			$sq = $qb->query();
			$sq->bindParameter('lastUpdate', $lastUpdate);
			$sq->bindParameter('processing', false);
			$sq->bindParameter('userId', 0);

			$identifiers = $sq->getResults($sq->getRowsConverter()->addStrCol('identifier')->singleColumn('identifier'));
			foreach ($identifiers as $identifier)
			{
				$cart = $cartManager->getCartByIdentifier($identifier);
				if ($cart)
				{
					$logging->info('Cleanup anonymous cart: ' . $identifier);
					$commerceServices->getCartManager()->deleteCart($cart);
				}
			}

			//Remove reservations of autheticated user
			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->innerJoin($fb->table('rbs_stock_dat_res'),
				$fb->logicAnd(
					$fb->eq($fb->column('target', 'rbs_stock_dat_res'), $fb->column('identifier', 'rbs_commerce_dat_cart'))
				));
			$qb->where(
				$fb->logicAnd(
					$fb->lte($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing')),
					$fb->eq($fb->column('confirmed', 'rbs_stock_dat_res'), $fb->booleanParameter('confirmed'))
			));

			$qb->distinct();

			$sq = $qb->query();
			$sq->bindParameter('lastUpdate', $lastUpdate);
			$sq->bindParameter('processing', false);
			$sq->bindParameter('confirmed', false);

			$stockManager = $commerceServices->getStockManager();
			$identifiers = $sq->getResults($sq->getRowsConverter()->addStrCol('identifier')->singleColumn('identifier'));
			foreach ($identifiers as $identifier)
			{
				$logging->info('Cleanup reservations cart: ' . $identifier);
				$stockManager->cleanupReservations($identifier);
			}

			// Remove duplicated cart for same user and store
			// SELECT MAX(`last_update`) AS `last_update`, COUNT(`id`) AS `countCart`, `user_id`, `store_id`
			// FROM `rbs_commerce_dat_cart`
			// WHERE (`user_id` <> :userId AND `processing` = :processing)
			// GROUP BY `user_id`, `store_id`
			// HAVING `countCart` > :countCart

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select(
				$fb->alias($fb->func('MAX', $fb->column('last_update')), 'last_update'),
				$fb->alias($fb->func('COUNT', $fb->column('id')), 'countCart'),
				$fb->column('user_id'), $fb->column('store_id')
			);
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->logicAnd(
					$fb->neq($fb->column('user_id'), $fb->integerParameter('userId')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing'))
				)
			);
			$qb->group($fb->column('user_id'))->group($fb->column('store_id'));
			$having = new \Change\Db\Query\Clauses\HavingClause($fb->gt($fb->identifier('countCart'), $fb->integerParameter('countCart')));
			$sq = $qb->query();
			$sq->setHavingClause($having);
			$sq->bindParameter('userId', 0);
			$sq->bindParameter('processing', false);
			$sq->bindParameter('countCart', 1);

			$result = $sq->getResults($sq->getRowsConverter()->addDtCol('last_update')->addIntCol('countCart', 'user_id', 'store_id'));

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->logicAnd(
					$fb->lt($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing')),
					$fb->eq($fb->column('user_id'), $fb->integerParameter('userId')),
					$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId'))
				)
			);

			$sq = $qb->query();
			foreach ($result as $row)
			{
				$sq->bindParameter('lastUpdate', $row['last_update']);
				$sq->bindParameter('processing', false);
				$sq->bindParameter('userId', $row['user_id']);
				$sq->bindParameter('storeId', $row['store_id']);
				$identifiers = $sq->getResults($sq->getRowsConverter()->addStrCol('identifier')->singleColumn('identifier'));
				foreach ($identifiers as $identifier)
				{
					$cart = $cartManager->getCartByIdentifier($identifier);
					if ($cart)
					{
						$logging->info('Cleanup deprecated cart: ' . $identifier . ',' . $row['user_id'] . ',' . $row['store_id']);
						$commerceServices->getCartManager()->deleteCart($cart);
					}
				}
			}
		}
		else
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
		}

		$reportedAt = new \DateTime();
		$reportedAt->add(new \DateInterval('PT' . $reportedAtSeconds . 'S'));
		$event->reported($reportedAt);
	}
} 
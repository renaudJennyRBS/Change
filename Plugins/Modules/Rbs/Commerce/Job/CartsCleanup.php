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
		$reportedAtSecondes = 20 * 60;

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$ttl = $commerceServices->getCartManager()->getCleanupTTL();
			$reportedAtSecondes = max(60, intval($ttl / 3));


			$lastUpdate = (new \DateTime())->sub(new \DateInterval('PT' . $ttl . 'S'));

			$dbProvider = $event->getApplicationServices()->getDbProvider();


			$upqb = $dbProvider->getNewQueryBuilder();
			$fb = $upqb->getFragmentBuilder();
			$profileTable = $fb->getDocumentTable('Rbs_Commerce_Profile');

			$upqb->select($fb->getDocumentColumn('id'))
				->from($profileTable)
				->where(
					$fb->eq($fb->column('lastcartidentifier'), $fb->column('identifier', 'rbs_commerce_dat_cart'))
				);
			$storedCart = $upqb->query();

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->logicAnd(
					$fb->lte($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate')),
					$fb->eq($fb->column('processing'), $fb->booleanParameter('processing')),
					$fb->notExists($storedCart)
				)
			);

			$sq = $qb->query();
			$sq->bindParameter('lastUpdate', $lastUpdate);
			$sq->bindParameter('processing', false);

			$identifiers = $sq->getResults($sq->getRowsConverter()->addStrCol('identifier')->singleColumn('identifier'));
			foreach ($identifiers as $identifier)
			{
				$cart = $cartManager->getCartByIdentifier($identifier);
				if ($cart)
				{
					$commerceServices->getCartManager()->deleteCart($cart);
				}
			}

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('identifier'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$qb->innerJoin($profileTable,
				$fb->logicAnd(
					$fb->eq($fb->column('lastcartidentifier', $profileTable), $fb->column('identifier', 'rbs_commerce_dat_cart'))
				));
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
				$stockManager->cleanupReservations($identifier);
			}
		}
		else
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
		}

		$reportedAt = new \DateTime();
		$reportedAt->add(new \DateInterval('PT' . $reportedAtSecondes . 'S'));
		$event->reported($reportedAt);
	}
} 
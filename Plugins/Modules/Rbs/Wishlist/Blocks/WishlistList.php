<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Wishlist\Blocks\WishlistList
 */
class WishlistList extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('userId');
		$parameters->addParameterMeta('storeId');
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$commerceServices = $event->getServices('commerceServices');
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('storeId', $webStore->getId());
		}

		if ($parameters->getParameter('userId') === null)
		{
			$userId = $event->getHttpRequest()->getQuery('userId');
			$user = null;
			if ($userId)
			{
				$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($userId);
				if ($user instanceof \Rbs\User\Documents\User)
				{
					$parameters->setParameterValue('userId', $user->getId());
				}
			}
			else
			{
				$parameters->setParameterValue('userId', $event->getAuthenticationManager()->getCurrentUser()->getId());
				$parameters->setParameterValue('isUserWishlist', true);
			}
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$user = $documentManager->getDocumentInstance($parameters->getParameterValue('userId'));
		$store = $documentManager->getDocumentInstance($parameters->getParameterValue('storeId'));

		if ($user instanceof \Rbs\User\Documents\User && $store instanceof \Rbs\Store\Documents\WebStore)
		{
			$isUserWishlists = $parameters->getParameterValue('isUserWishlist');
			$attributes['isUserWishlists'] = $isUserWishlists;

			$dqb = $documentManager->getNewQuery('Rbs_Wishlist_Wishlist');
			$andPredicate = new \Change\Db\Query\Predicates\Conjunction();
			$andPredicate->setArguments([$dqb->eq('user', $user), $dqb->eq('store', $store)]);
			if (!$isUserWishlists)
			{
				$andPredicate->addArgument($dqb->eq('public', true));
			}
			$dqb->setPredicate($andPredicate);

			$attributes['wishlists'] = [];
			foreach ($dqb->getDocuments() as $wishlist)
			{
				/* @var $wishlist \Rbs\Wishlist\Documents\Wishlist */
				$visual = null;
				if ($wishlist->getProductsCount())
				{
					$firstVisual = $wishlist->getProducts()[0]->getFirstVisual();
					$visual = ['alt' => $firstVisual->getCurrentLocalization()->getAlt(), 'src' => $firstVisual->getPublicURL(100, 100)];
				}
				$attributes['wishlists'][] = [
					'id' => $wishlist->getId(),
					'title' => $wishlist->getTitle(),
					'productCount' => $wishlist->getProductsCount(),
					'visual' => $visual,
					'default' => $wishlist->getDefault(),
					'public' => $wishlist->getPublic()
				];
			}
			$attributes['data'] = [
				'userId' => $user->getId()
			];

			return 'wishlist-list.twig';
		}
		/*
		else
		{
			$unauthorized = new \Change\Http\Result();
			$unauthorized->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_401);
			$event->setResult($unauthorized);
			return $unauthorized;
		}
		*/
		return 'wishlist-list-error.twig';
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Wishlist\Blocks\WishlistButton
 */
class WishlistButton extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('productIds');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$commerceServices = $event->getServices('commerceServices');
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('storeId', $webStore->getId());
		}

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('userId', $user->getId());
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Wishlist_Wishlist');
			$dqb->andPredicates($dqb->eq('user', $user));
			$parameters->setParameterValue('wishlistIds', $dqb->getDocuments()->ids());
		}
		else
		{
			$parameters->setParameterValue('userId', false);
		}

		$productIds = $parameters->getParameter('productIds');
		if ($productIds !== null)
		{
			$parameters->setParameterValue('productIds', $productIds);
		}
		else
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Catalog\Documents\Product)
			{
				$parameters->setParameterValue('productIds', [$document->getId()]);
			}
			//TODO manage with product list
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$userId = $parameters->getParameterValue('userId');
		$storeId = $parameters->getParameterValue('storeId');
		$productIds = $parameters->getParameterValue('productIds');
		if ($userId && $storeId && $productIds)
		{
			$wishlists = [];
			$urlManager = $event->getUrlManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$wishlistIds = $parameters->getParameterValue('wishlistIds');
			foreach ($wishlistIds as $wishlistId)
			{
				$wishlist = $documentManager->getDocumentInstance($wishlistId);
				if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist)
				{
					$wishlists[] = [
						'title' => $wishlist->getTitle(),
						'id' => $wishlist->getId(),
						'href' => $urlManager->getByDocument($wishlist, $event->getParam('website'))->normalize()->toString()
					];
				}
			}
			$attributes['data'] = [
				'productIds' => $productIds,
				'wishlists' => $wishlists,
				'userId' => $userId,
				'storeId' => $storeId
			];
			return 'wishlist-button.twig';
		}
		return null;
	}
}
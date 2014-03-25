<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist\Http\Web;

use Change\Http\Web\Event;

/**
* @name \Rbs\Wishlist\Http\Web\AddWishlist
*/
class AddWishlist extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$data = $event->getRequest()->getPost()->toArray();
			$title = isset($data['title']) ? $data['title'] : null;
			$storeId = isset($data['storeId']) ? $data['storeId'] : null;
			$userId = isset($data['userId']) ? $data['userId'] : null;
			$public = isset($data['public']) ? $data['public'] : null;
			$productIds = isset($data['productIds']) ? $data['productIds'] : null;

			if ($title && $storeId && $userId && $public !== null && $event->getAuthenticationManager()->getCurrentUser()->getId() === $userId)
			{
				$commerceServices = $event->getServices('commerceServices');
				if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
				{
					$wishlistManager = $commerceServices->getWishlistManager();
					$wishlist = $wishlistManager->addWishlist($title, $public, $storeId, $userId, $productIds);
					if ($wishlist)
					{
						if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist)
						{
							$productTitles = [];
							foreach ($wishlist->getProducts() as $product)
							{
								$productTitles[] = $product->getCurrentLocalization()->getTitle();
							}
							$replacements = ['wishlist' => $title, 'products' => implode(', ', $productTitles)];
							$data['success'] = $event->getApplicationServices()->getI18nManager()
								->trans('m.rbs.wishlist.front.wishlist_add_wishlist_success', ['ucf'], $replacements);
						}
						else if (is_array($wishlist) && isset($wishlist['error']))
						{
							$data['error'] = $wishlist['error'];
						}
					}
				}
				else
				{
					$data['error'] = 'CommerceServices not set';
				}
			}
			else
			{
				$data['error'] = 'Invalid parameters';
			}
			$result = new \Change\Http\Web\Result\AjaxResult($data);
			if (isset($data['error']) && $data['error'])
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			}
			$event->setResult($result);
		}
	}
}
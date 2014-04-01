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
* @name \Rbs\Wishlist\Http\Web\DeleteWishlist
*/
class DeleteWishlist extends \Change\Http\Web\Actions\AbstractAjaxAction
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
			$wishlistId = isset($data['wishlistId']) ? $data['wishlistId'] : null;
			$userId = isset($data['userId']) ? $data['userId'] : null;

			if ($wishlistId && $userId && $event->getAuthenticationManager()->getCurrentUser()->getId() === $userId)
			{
				$wishlist = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($wishlistId);
				if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist && $wishlist->getUserId() === $userId)
				{
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();
						$wishlist->delete();
						$tm->commit();
					}
					catch (\Exception $e)
					{
						$tm->rollBack($e);
					}

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
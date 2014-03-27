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
* @name \Rbs\Wishlist\Http\Web\UpdateWishlist
*/
class UpdateWishlist extends \Change\Http\Web\Actions\AbstractAjaxAction
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

			$productIdsToAdd = isset($data['productIdsToAdd']) ? $data['productIdsToAdd'] : null;
			$title = isset($data['title']) ? $data['title'] : null;
			$productIdsToRemove = isset($data['productIdsToRemove']) ? $data['productIdsToRemove'] : null;
			$changeIsPublic = isset($data['changeIsPublic']) ? $data['changeIsPublic'] : null;
			$setDefaultWishlist = isset($data['setDefault']) ? $data['setDefault'] : null;

			if ($wishlistId && $userId && $event->getAuthenticationManager()->getCurrentUser()->getId() === $userId)
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$wishlist = $documentManager->getDocumentInstance($wishlistId);
				if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist && $wishlist->getUserId() === $userId)
				{
					$commerceServices = $event->getServices('commerceServices');
					if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
					{
						$i18nManager = $event->getApplicationServices()->getI18nManager();
						if (is_array($productIdsToAdd))
						{
							$this->addProductsInWishlist($productIdsToAdd, $wishlist, $data, $documentManager, $i18nManager);
						}
						else if ($title)
						{
							$this->changeWishlistTitle($title, $wishlist, $data, $documentManager, $i18nManager);
						}
						else if (is_array($productIdsToRemove))
						{
							$this->removeProductsFromWishlist($productIdsToRemove, $wishlist, $documentManager);
						}
						else if ($changeIsPublic !== null)
						{
							$wishlist->setPublic($changeIsPublic);
						}
						else if ($setDefaultWishlist === true)
						{
							$this->setDefaultWishlist($wishlist, $data, $documentManager, $event->getApplicationServices()->getTransactionManager());
						}
						if (!isset($data['error']))
						{
							$wishlistManager = $commerceServices->getWishlistManager();
							$result = $wishlistManager->updateWishlist($wishlist);
							if ($result && isset($result['error']))
							{
								$data['error'] = $result['error'];
							}
						}
					}
					else
					{
						$data['error'] = 'CommerceServices not set';
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

	/**
	 * @param integer[] $productIdsToAdd
	 * @param \Rbs\Wishlist\Documents\Wishlist $wishlist
	 * @param array $data
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	protected function addProductsInWishlist($productIdsToAdd, $wishlist, &$data, $documentManager, $i18nManager)
	{
		$productTitles = [];
		foreach ($productIdsToAdd as $productId)
		{
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$wishlist->getProducts()->add($product);
				$productTitles[] = $product->getCurrentLocalization()->getTitle();
			}
		}
		if (count($productTitles))
		{
			$replacements = ['products' => implode(', ', $productTitles), 'wishlist' => $wishlist->getTitle()];
			$data['success'] = $i18nManager->trans('m.rbs.wishlist.front.wishlist_products_add_success', ['ucf'], $replacements);
		}
	}

	/**
	 * @param string $newTitle
	 * @param \Rbs\Wishlist\Documents\Wishlist $wishlist
	 * @param array $data
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	protected function changeWishlistTitle($newTitle, $wishlist, &$data, $documentManager, $i18nManager)
	{
		//check if there is no wishlist with this title
		$dqb = $documentManager->getNewQuery('Rbs_Wishlist_Wishlist');
		$dqb->andPredicates($dqb->eq('title', $newTitle), $dqb->eq('user', $wishlist->getUser()));
		if ($dqb->getCountDocuments() === 0)
		{
			$wishlist->setTitle($newTitle);
		}
		else
		{
			$data['error'] = $i18nManager->trans('m.rbs.wishlist.front.wishlist_error_wishlist_already_exist', ['ucf'], ['wishlist' => $newTitle]);
		}
	}

	/**
	 * @param array $productIdsToRemove
	 * @param \Rbs\Wishlist\Documents\Wishlist $wishlist
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	protected function removeProductsFromWishlist($productIdsToRemove, $wishlist, $documentManager)
	{
		foreach ($productIdsToRemove as $productIdToRemove => $toRemove)
		{
			if ($toRemove)
			{
				$product = $documentManager->getDocumentInstance($productIdToRemove);
				if ($product instanceof \Rbs\Catalog\Documents\Product)
				{
					$wishlist->getProducts()->remove($product);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Wishlist\Documents\Wishlist $wishlist
	 * @param array $data
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 */
	protected function setDefaultWishlist($wishlist, &$data, $documentManager, $transactionManager)
	{
		//get all user wishlist to set default to false on them, after that, set default to our wishlist
		$dqb = $documentManager->getNewQuery('Rbs_Wishlist_Wishlist');
		$dqb->andPredicates($dqb->eq('user', $wishlist->getUser()));
		$wishlists = $dqb->getDocuments();

		try
		{
			$transactionManager->begin();
			foreach ($wishlists as $otherWishlist)
			{
				/* @var $otherWishlist \Rbs\Wishlist\Documents\Wishlist */
				$otherWishlist->setDefault(false);
				$otherWishlist->save();
			}
			$transactionManager->commit();
			$wishlist->setDefault(true);
		}
		catch(\Exception $e)
		{
			$transactionManager->rollBack($e);
			$data['error'] = $e->getMessage();
		}
	}
}
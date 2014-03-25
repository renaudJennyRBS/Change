<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist;

/**
 * @name \Rbs\Wishlist\WishlistManager
 */
class WishlistManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'WishlistManager';

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Wishlist/Events/WishlistManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('addWishlist', [$this, 'onDefaultAddWishlist'], 5);
		$eventManager->attach('addProductsToWishlist', [$this, 'onDefaultAddProductsToWishlist'], 5);
		$eventManager->attach('updateWishlist', [$this, 'onDefaultUpdateWishlist'], 5);
	}

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param string $title
	 * @param boolean $public
	 * @param integer $storeId
	 * @param integer $userId
	 * @param array $productIds
	 * @return \Rbs\Wishlist\Documents\Wishlist|array [error => localizedErrorString] |null
	 */
	public function addWishlist($title, $public, $storeId, $userId, $productIds)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'title' => $title,
			'public' => $public,
			'storeId' => $storeId,
			'userId' => $userId,
			'productIds' => $productIds
		));
		$eventManager->trigger('addWishlist', $this, $args);
		if (isset($args['wishlist']) && $args['wishlist'] instanceof \Rbs\Wishlist\Documents\Wishlist)
		{
			return $args['wishlist'];
		}
		else if (isset($args['error']))
		{
			return ['error' => $args['error']];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultAddWishlist($event)
	{
		$data = $event->getParams();
		$title = isset($data['title']) ? $data['title'] : null;
		$public = isset($data['public']) ? $data['public'] : null;
		$storeId = isset($data['storeId']) ? $data['storeId'] : null;
		$userId = isset($data['userId']) ? $data['userId'] : null;
		$productIds = isset($data['productIds']) && is_array($data['productIds']) ? $data['productIds'] : [];

		if ($title && $public !== null && $storeId && $userId)
		{
			$documentManager = $this->getDocumentManager();
			$store = $documentManager->getDocumentInstance($storeId);
			$user = $documentManager->getDocumentInstance($userId);
			if ($store instanceof \Rbs\Store\Documents\WebStore && $user instanceof \Rbs\User\Documents\User)
			{
				//Check first if a wishlist already exist with this title for this user and store
				$dqb = $documentManager->getNewQuery('Rbs_Wishlist_Wishlist');
				$dqb->andPredicates($dqb->eq('store', $store), $dqb->eq('user', $user), $dqb->eq('title', $title));
				if ($dqb->getCountDocuments() === 0)
				{
					/* @var $wishlist \Rbs\Wishlist\Documents\Wishlist */
					$wishlist = $documentManager->getNewDocumentInstanceByModelName('Rbs_Wishlist_Wishlist');
					$wishlist->setTitle($title);
					$wishlist->setUser($user);
					$wishlist->setStore($store);
					$wishlist->setPublic($public);
					//check if this wishlist is the first one, in that case set default wishlist for this user
					$dqb = $documentManager->getNewQuery('Rbs_Wishlist_Wishlist');
					$dqb->andPredicates($dqb->eq('store', $store), $dqb->eq('user', $user));
					if ($dqb->getCountDocuments() === 0)
					{
						$wishlist->setDefault(true);
					}

					$products = [];
					foreach ($productIds as $productId)
					{
						$products[] = $documentManager->getDocumentInstance($productId);
					}
					//this function will save the wishlist, so we don't have to do here
					$result = $this->addProductsToWishlist($wishlist, $products);
					if ($result && isset($result['error']))
					{
						$event->setParam('error', $result['error']);
					}
					else
					{
						$event->setParam('wishlist', $wishlist);
					}
				}
				else
				{
					$event->setParam('error', $this->getUserLocalizedString(
						$user, 'm.rbs.wishlist.front.wishlist_error_wishlist_already_exist', $event->getApplicationServices()
					), ['ucf'], ['wishlist' => $title]);
				}
			}
		}
	}

	public function updateWishlist($wishlist)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'wishlist' => $wishlist
		));
		$eventManager->trigger('updateWishlist', $this, $args);
		return isset($args['error']) ? ['error' => $args['error']] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultUpdateWishlist($event)
	{
		$data = $event->getParams();
		$wishlist = isset($data['wishlist']) ? $data['wishlist'] : null;
		
		if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$wishlist->update();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
				$data['error'] = $e->getMessage();
			}
		}
	}

	/**
	 * @param $wishlist
	 * @param $products
	 * @return null|array [error => localizedErrorString]
	 */
	public function addProductsToWishlist($wishlist, $products)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'wishlist' => $wishlist,
			'products' => $products
		));
		$eventManager->trigger('addProductsToWishlist', $this, $args);
		return isset($args['error']) ? ['error' => $args['error']] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultAddProductsToWishlist($event)
	{
		$data = $event->getParams();
		$wishlist = isset($data['wishlist']) ? $data['wishlist'] : null;
		$products = isset($data['products']) ? $data['products'] : null;

		if ($wishlist instanceof \Rbs\Wishlist\Documents\Wishlist && is_array($products))
		{
			$wishlist->setProducts($products);
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$wishlist->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
				$event->setParam('error', $this->getUserLocalizedString(
					$wishlist->getUser(), 'm.rbs.wishlist.front.wishlist_error_can_t_add_products_to_wishlist', $event->getApplicationServices()
				));
				$event->getApplication()->getLogging()->error('couldn\'t save the wishlist ' . $wishlist->getTitle());
			}
		}
	}

	/**
	 * @param \Rbs\User\Documents\User $user
	 * @param string $key
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param array $formatters
	 * @param array $replacements
	 * @return string|null
	 */
	protected function getUserLocalizedString($user, $key, $applicationServices, $formatters = [], $replacements = [])
	{
		$authenticatedUser = new \Rbs\User\Events\AuthenticatedUser($user);
		$i18nManager = $applicationServices->getI18nManager();
		$profileManager = $applicationServices->getProfileManager();
		$userProfile = $profileManager->loadProfile($authenticatedUser, 'Change_User');
		$userLCID = $userProfile->getPropertyValue('LCID') != null ? $userProfile->getPropertyValue('LCID') : $i18nManager->getDefaultLCID();
		try
		{
			$applicationServices->getDocumentManager()->pushLCID($userLCID);
			$localizedString = $i18nManager->trans($key, $formatters, $replacements);
			$applicationServices->getDocumentManager()->popLCID();
			return $localizedString;
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->fatal($e);
			$applicationServices->getDocumentManager()->popLCID();
		}
		return null;
	}
}
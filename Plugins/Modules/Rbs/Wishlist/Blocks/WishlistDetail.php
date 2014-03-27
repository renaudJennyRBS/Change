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
 * @name \Rbs\Wishlist\Blocks\WishlistDetail
 */
class WishlistDetail extends Block
{
	/**
	 * @var array
	 */
	protected $validSortBy = ['title.asc', 'price.asc', 'price.desc', 'price.desc', 'dateAdded.desc'];

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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('userId', $user->getId());
		}

		$this->setParameterValueForDetailBlock($parameters, $event);

		return $parameters;
	}
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Wishlist\Documents\Wishlist)
		{
			return true;
		}
		return false;
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
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		/* @var $wishlist \Rbs\Wishlist\Documents\Wishlist */
		$wishlist = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));

		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			return null;
		}

		$isUserWishlist = $wishlist->getUserId() == $parameters->getParameterValue('userId');
		if (!$wishlist->getPublic() && !$isUserWishlist)
		{
			$attributes['unauthorized'] = true;
			return 'wishlist-detail.twig';
		}

		if ($wishlist)
		{
			$urlManager = $event->getUrlManager();
			$stockManager = $commerceServices->getStockManager();

			$attributes['products'] = [];
			foreach ($wishlist->getProducts() as $product)
			{
				$availability = null;
				if ($product->getSku())
				{
					$availability = $stockManager->getInventoryThresholdTitle($stockManager->getInventoryThreshold($product->getSku()));
				}
				$attributes['products'][] = [
					'title' => $product->getCurrentLocalization()->getTitle(),
					'id' => $product->getId(),
					'visual' => [
						'src' => $product->getFirstVisual()->getPublicURL(100, 100),
						'alt' => $product->getFirstVisual()->getCurrentLocalization()->getAlt()
					],
																  //FIXME
//					'url' => $urlManager->getByDocument($product, $product->getPublicationSections()[0])->normalize()->toString(),
					'availability' => $availability
				];
			}
			$attributes['productCountWarning'] = $this->getProductCountWarning($wishlist);
			$attributes['isUserWishlist'] = $isUserWishlist;
			$attributes['data'] = [
				'userId' => $wishlist->getUserId(),
				'wishlistId' => $wishlist->getId(),
				'title' => $wishlist->getTitle(),
				'productIds' => $wishlist->getProductsIds(),
				'public' => $wishlist
			];
			return 'wishlist-detail.twig';
		}
		return null;
	}

	/**
	 * @param \Rbs\Wishlist\Documents\Wishlist $wishlist
	 * @return false|array
	 */
	protected function getProductCountWarning($wishlist)
	{
		//if the product count exceeds 80% of wishlist products max quantity, return data to explain it to the user
		$maxOccurs = $wishlist->getDocumentModel()->getProperty('products')->getMaxOccurs();
		$count = $wishlist->getProductsCount();
		if ($count > $maxOccurs * 0.8)
		{
			return ['count' => $count, 'max' => $maxOccurs];
		}
		return false;
	}
}
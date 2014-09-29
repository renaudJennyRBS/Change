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

		$parameters->addParameterMeta('displayPricesWithoutTax');
		$parameters->addParameterMeta('displayPricesWithTax');
		$parameters->addParameterMeta('imageFormats', 'listItem,pictogram');

		$parameters->addParameterMeta('userId');

		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('zone');

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('userId', $user->getId());
		}

		$this->setParameterValueForDetailBlock($parameters, $event);

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPricesWithoutTax') === null)
			{
				$parameters->setParameterValue('displayPricesWithoutTax', $webStore->getDisplayPricesWithoutTax());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}

			$billingArea = $commerceServices->getContext()->getBillingArea();
			if ($billingArea)
			{
				$parameters->setParameterValue('billingAreaId', $billingArea->getId());
			}

			$zone = $commerceServices->getContext()->getZone();
			if ($zone)
			{
				$parameters->setParameterValue('zone', $zone);
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('billingAreaId', 0);
			$parameters->setParameterValue('zone', null);
			$parameters->setParameterValue('displayPricesWithoutTax', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

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

		/* @var $wishList \Rbs\Wishlist\Documents\Wishlist */
		$wishList = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));

		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			return null;
		}

		$isUserWishList = $wishList->getUserId() == $parameters->getParameterValue('userId');
		if (!$wishList->getPublic() && !$isUserWishList)
		{
			$attributes['unauthorized'] = true;
			return 'wishlist-detail.twig';
		}

		if ($wishList)
		{
			$urlManager = $event->getUrlManager();
			$context = new \Change\Http\Ajax\V1\Context($event->getApplication(), $documentManager);
			if ($urlManager instanceof \Change\Http\Web\UrlManager)
			{
				$context->setWebsiteUrlManager($urlManager);
				$context->setWebsite($urlManager->getWebsite());
				$context->setURLFormats('canonical');
			}

			$context->setDetailed(false);
			$context->setVisualFormats($parameters->getParameter('imageFormats'));
			$context->addData('webStoreId', $parameters->getParameter('webStoreId'));
			$context->addData('billingAreaId', $parameters->getParameter('billingAreaId'));
			$context->addData('zone', $parameters->getParameter('zone'));

			$catalogManager = $commerceServices->getCatalogManager();
			$contextArray = $context->toArray();

			$rows = array();
			foreach ($wishList->getProducts() as $product)
			{
				$productData = $catalogManager->getProductData($product, $contextArray);
				if ($productData)
				{
					$rows[] = $productData;
				}
			}
			$attributes['rows'] = $rows;
			$attributes['productCountWarning'] = $this->getProductCountWarning($wishList);
			$attributes['isUserWishlist'] = $isUserWishList;
			$attributes['data'] = [
				'userId' => $wishList->getUserId(),
				'wishlistId' => $wishList->getId(),
				'title' => $wishList->getTitle(),
				'productIds' => $wishList->getProductsIds(),
				'public' => $wishList
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
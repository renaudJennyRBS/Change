<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Catalog\Blocks\ProductAddedToCart
 */
class ProductAddedToCart extends \Change\Presentation\Blocks\Standard\Block
{
	use \Rbs\Commerce\Blocks\Traits\ContextParameters;

	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('imageFormats', 'listItem');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$this->initCommerceContextParameters($parameters);
		$this->setParameterValueForDetailBlock($parameters, $event);

		$parameters->addParameterMeta('pageId', null);

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$this->setCommerceContextParameters($commerceServices->getContext(), $parameters);
		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\Product && $document->published())
		{
			return true;
		}
		return false;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$productId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$catalogManager = $commerceServices->getCatalogManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
			$productData = $catalogManager->getProductData($productId, $context->toArray());

			$attributes['productData'] = $productData;
			return 'product-added-to-cart.twig';

		}
		return null;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(false);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setPage($parameters->getParameter('pageId'));

		$context->addData('webStoreId', $parameters->getParameter('webStoreId'));
		$context->addData('billingAreaId', $parameters->getParameter('billingAreaId'));
		$context->addData('zone', $parameters->getParameter('zone'));
		return $context;
	}
} 
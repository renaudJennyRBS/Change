<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;

/**
 * @name \Rbs\Catalog\Blocks\ProductSet
 */
class ProductSet extends \Rbs\Catalog\Blocks\Product
{
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\ProductSet && $document->published())
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
		$productId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\ProductSet */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\ProductSet)
			{
				$options = [ 'urlManager' => $event->getUrlManager() ];
				$productPresentation = $commerceServices->getCatalogManager()->getProductPresentation($product, $options);
				$productPresentation->evaluate();
				$attributes['productPresentation'] = $productPresentation;

				$subProductsPresentation = array();
				foreach($product->getProducts() as $p)
				{
					$pPresentation = $commerceServices->getCatalogManager()->getProductPresentation($p, $options);
					$pPresentation->evaluate();
					$subProductsPresentation[] = $pPresentation;
				}
				$attributes['subProductsPresentation'] = $subProductsPresentation;

				return 'productset.twig';
			}

			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$attributes['section'] = $page->getSection();
		}
		return null;
	}
}
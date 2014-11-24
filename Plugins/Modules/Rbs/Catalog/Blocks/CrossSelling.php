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
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\CrossSelling
 */
class CrossSelling extends Block
{
	use \Rbs\Commerce\Blocks\Traits\ContextParameters;

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
		$parameters->addParameterMeta('title');
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('imageFormats', 'listItem,pictogram');
		$parameters->addParameterMeta('crossSellingType', 'ACCESSORIES');

		$parameters->addParameterMeta('itemsPerSlide', 3);
		$parameters->addParameterMeta('slideCount');

		$this->initCommerceContextParameters($parameters);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$this->setParameterValueForDetailBlock($parameters, $event);

		$document = $event->getParam('document');
		if ($document instanceof \Rbs\Catalog\Documents\Product && $document->published())
		{
			$parameters->setParameterValue('productId', $document->getId());
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
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$productId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$crossSellingType = $parameters->getParameter('crossSellingType');

		if ($productId && $crossSellingType)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$rows = array();
			$product = $documentManager->getDocumentInstance($productId, 'Rbs_Catalog_Product');
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$csParameters = array();
				$csParameters['urlManager'] = $event->getUrlManager();
				$csParameters['crossSellingType'] = $crossSellingType;
				$csParameters['visualFormats'] = $parameters->getParameter('imageFormats');
				$csParameters['webStoreId'] = $parameters->getParameter('webStoreId');
				$csParameters['billingAreaId'] = $parameters->getParameter('billingAreaId');
				$csParameters['zone'] = $parameters->getParameter('zone');
				$rows = $commerceServices->getProductManager()->getCrossSellingForProduct($product, $csParameters);
			}

			$attributes['rows'] = $rows;
			$attributes['itemsPerSlide'] = $parameters->getParameter('itemsPerSlide');
			if (count($rows) && isset($attributes['itemsPerSlide']))
			{
				$attributes['slideCount'] = ceil(count($rows)/$attributes['itemsPerSlide']);
			}

			return 'product-list-slider.twig';
		}
		return null;
	}
}
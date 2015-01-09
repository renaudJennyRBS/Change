<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Product
 */
class Brand extends Block
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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('imageFormats', 'detail');
		$parameters->addParameterMeta('dataSetNames', '');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return $document instanceof \Rbs\Brand\Documents\Brand && $document->published();
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
		$brand = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		if ($brand instanceof \Rbs\Brand\Documents\Brand)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$brandManager = $commerceServices->getBrandManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
			$section = $event->getParam('section');
			if ($section)
			{
				$context->setSection($section);
			}
			$attributes['brandData'] = $brandManager->getBrandData($brand, $context->toArray());

			return 'brand.twig';
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
		$context->setDetailed(true);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical', 'contextual']);
		$context->setDataSetNames($parameters->getParameter('dataSetNames'));
		$context->setPage($parameters->getParameter('pageId'));
		return $context;
	}
}
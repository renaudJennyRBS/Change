<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Blocks;

/**
 * @name \Rbs\Storelocator\Blocks\Store
 */
class Store extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('imageFormats', 'x,listItem,pictogram');
		$parameters->setLayoutParameters($event->getBlockLayout());
		$this->setParameterValueForDetailBlock($parameters, $event);

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
		return ($document instanceof \Rbs\Storelocator\Documents\Store && $document->published());
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout(), getBlockParameters(), getBlockResult(),
	 *        getApplication, getApplicationServices, getServices, getUrlManager()
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$storeId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($storeId)
		{
			$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
			if ($storelocatorServices instanceof \Rbs\Storelocator\StorelocatorServices)
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
				$section = $event->getParam('section');
				if ($section)
				{
					$context->setSection($section);
				}
				else
				{
					$website =  $event->getParam('website');
					if ($website)
					{
						$context->setWebsite($website);
					}
				}

				$storeData = $storelocatorServices->getStoreManager()->getStoreData($storeId, $context->toArray());
				if ($storeData)
				{
					$attributes['storeData'] = $storeData;

					$controllerInit = [];
					$controllerInit['googleAPIKey'] = $event->getApplication()->getConfiguration('Rbs/Geo/Google/APIKey');
					$controllerInit['storeData'] = $storeData;
					$attributes['controllerInit'] = $controllerInit;
					return 'store.twig';
				}
			}
		}
		return null;
	}


	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Presentation\Blocks\Parameters $parameters
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
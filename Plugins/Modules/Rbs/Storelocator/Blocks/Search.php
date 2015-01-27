<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Blocks;

/**
 * @name \Rbs\Storelocator\Blocks\Search
 */
class Search extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('imageFormats', 'x,listItem');
		$parameters->addParameterMeta('dataSetNames', 'coordinates,address,card,allow');
		$parameters->addParameterMeta('URLFormats', 'canonical');
		$parameters->addParameterMeta('pagination', '0,50');
		$parameters->addParameterMeta('facet');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters->addParameterMeta('facetFilters', null);

		$facet = $parameters->getParameter('facet');
		if ($facet)
		{
			$request = $event->getHttpRequest();
			$queryFilters = $request ? $request->getQuery('facetFilters', null) : null;
			$facetFilters = $this->validateQueryFilters($queryFilters);
			$parameters->setParameterValue('facetFilters', $facetFilters);
		}
		return $parameters;
	}

	/**
	 * @param $queryFilters
	 * @return array
	 */
	protected function validateQueryFilters($queryFilters)
	{
		$facetFilters = array();
		if (is_array($queryFilters))
		{
			foreach ($queryFilters as $fieldName => $rawValue)
			{
				if (is_string($fieldName) && $rawValue)
				{
					$facetFilters[$fieldName] = $rawValue;
				}
			}
			return $facetFilters;
		}
		return $facetFilters;
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
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);

		$controllerInit = ['searchContext' => $context->toQueryParams()];
		$controllerInit['searchContext']['data']['distance'] = '50km';
		$controllerInit['googleAPIKey'] = $event->getApplication()->getConfiguration('Rbs/Geo/Google/APIKey');

		$commercialSign = $documentManager->getDocumentInstance($parameters->getParameter('commercialSignId'));
		$commercialSignId = ($commercialSign instanceof \Rbs\Storelocator\Documents\CommercialSign) ? $commercialSign->getId() : null;

		if ($commercialSign)
		{
			$controllerInit['searchContext']['data']['commercialSign'] = $commercialSignId;

			$attributes['commercialSignTitle'] = $commercialSign->getCurrentLocalization()->getTitle();
			$img = $commercialSign->getMarker();
			if ($img instanceof \Rbs\Media\Documents\Image)
			{
				$markerIcon = ['url' => $img->getPublicURL(), 'height' => $img->getHeight(), 'width' => $img->getWidth()];
				$controllerInit['markerIcon'] = $markerIcon;
			}
		}

		$facetFilters = $parameters->getParameter('facetFilters');
		$facet = intval($parameters->getParameter('facet'));
		if ($facetFilters && $facet)
		{
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

			$context->addData('facetFilters', $facetFilters);
			/** @var \Rbs\Storelocator\StorelocatorServices $storelocatorServices */
			$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
			$storeManager = $storelocatorServices->getStoreManager();
			$storesData = $storeManager->getStoresData($context->toArray());

			$attributes = $this->setFacetValueTitle($attributes, $facet, $facetFilters, $documentManager);
			$controllerInit['storesData'] = $storesData['items'];
		}

		$attributes['controllerInit'] = $controllerInit;


		if ($facet && !$facetFilters)
		{
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
			$context->addData('facets', [$facet]);

			/** @var \Rbs\Storelocator\StorelocatorServices $storelocatorServices */
			$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
			$storeManager = $storelocatorServices->getStoreManager();
			$facetsData = $storeManager->getFacetsData($context->toArray());
			$attributes['facetsData'] = $facetsData['items'];
		}
		else
		{
			$attributes['facetsData'] = [];
		}
		return 'search.twig';
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
		$context->setDetailed(false);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical', 'contextual']);
		$context->setDataSetNames($parameters->getParameter('dataSetNames'));
		$context->setPagination($parameters->getParameter('pagination'));
		return $context;
	}

	/**
	 * @param \ArrayObject $attributes
	 * @param integer $facetId
	 * @param array $facetFilters
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \ArrayObject
	 */
	protected function setFacetValueTitle($attributes, $facetId, $facetFilters, $documentManager)
	{
		$facetDocument = $documentManager->getDocumentInstance($facetId);
		if ($facetDocument instanceof \Rbs\Elasticsearch\Documents\Facet)
		{
			$facetDefinition = $facetDocument->getFacetDefinition();
			foreach ($facetFilters as $name => $values)
			{
				if ($name == $facetDefinition->getFieldName())
				{
					if (is_array($values))
					{
						foreach ($values as $key => $checked)
						{
							if (is_numeric($key))
							{
								$doc = $documentManager->getDocumentInstance($key);
								if ($doc)
								{
									$title = $doc->getDocumentModel()->getPropertyValue($doc, 'title');
									if ($title)
									{
										$attributes['facetValue'] = $key;
										$attributes['facetValueTitle'] = $title;
										return $attributes;
									}
								}
							}
						}
					}
				}
			}
		}
		return $attributes;
	}
}
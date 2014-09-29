<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
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
 * @name \Rbs\Catalog\Blocks\ProductList
 */
class ProductList extends Block
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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('useCurrentSectionProductList');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('showOrdering', true);
		$parameters->addParameterMeta('showUnavailable', true);
		$parameters->addParameterMeta('imageFormats', 'listItem,pictogram');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters->addParameterMeta('redirectUrl');

		$this->initCommerceContextParameters($parameters);

		$parameters->addParameterMeta('sortBy', null);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('conditionId');

		$parameters->addParameterMeta('facetFilters', null);
		$parameters->addParameterMeta('searchText', null);
		$parameters->addParameterMeta('pageId', null);

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}
		else
		{
			$page = null;
		}

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->setParameterValueForDetailBlock($parameters, $event);

		if ($parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) == null
			&& $parameters->getParameter('useCurrentSectionProductList') === true && $page)
		{
			$section = $page->getSection();
			$catalogManager = $commerceServices->getCatalogManager();
			$defaultProductList = $catalogManager->getDefaultProductListBySection($section);
			if ($this->isValidDocument($defaultProductList))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $defaultProductList->getId());
			}
		}

		if ($parameters->getParameter('showOrdering'))
		{
			$sortBy = $request->getQuery('sortBy-' . $event->getBlockLayout()->getId());
			if (!\Change\Stdlib\String::isEmpty($sortBy))
			{
				$parameters->setParameterValue('sortBy', trim($sortBy));
			}
		}

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$uri = $urlManager->getByFunction('Rbs_Commerce_Cart');
			if ($uri)
			{
				$parameters->setParameterValue('redirectUrl', $uri->normalize()->toString());
			}
		}

		$commerceContext = $commerceServices->getContext();
		$this->setCommerceContextParameters($commerceContext, $parameters);

		$queryFilters = $request->getQuery('facetFilters', null);
		$facetFilters = $this->validateQueryFilters($queryFilters);
		$parameters->setParameterValue('facetFilters', $facetFilters);
		$parameters->setParameterValue('searchText', $this->extractText($request));
		return $parameters;
	}

	/**
	 * @param \Change\Http\Request $request
	 * @return string|null
	 */
	protected function extractText($request)
	{
		$searchText = $request->getQuery('filterText');
		if (!\Change\Stdlib\String::isEmpty($searchText))
		{
			return  trim($searchText);
		}
		return null;
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\ProductList && $document->activated())
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
		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productListId)
		{
			$application = $event->getApplication();
			$logging = $application->getLogging();
			$applicationServices = $event->getApplicationServices();
			$documentManager = $applicationServices->getDocumentManager();

			/** @var $productList \Rbs\Catalog\Documents\ProductList|null */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$logging->warn(__METHOD__ . ': invalid product list');
				return null;
			}

			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$context = $this->populateContext($application, $documentManager, $parameters);

			$context->addData('listId', $productList->getId());

			$contextArray = $context->toArray();
			$result = $commerceServices->getCatalogManager()->getProductsData($contextArray);
			$attributes['productsData'] = $result['items'];
			$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');

			$pagination = $result['pagination'];
			$pagination['pageCount'] = $pageCount = ceil($pagination['count'] / $pagination['limit']);
			$pagination['pageNumber'] = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);
			$attributes['pagination'] = $pagination;
			return 'product-list.twig';
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
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$URLFormats = ['canonical'];
		if ($parameters->getParameter('contextualUrls'))
		{
			$URLFormats[] = 'contextual';
		}
		$context->setURLFormats($URLFormats);

		$pageNumber = intval($parameters->getParameter('pageNumber'));
		$limit = $parameters->getParameter('itemsPerPage');
		$offset = ($pageNumber - 1) * $limit;
		$context->setPagination([$offset, $limit]);
		$context->setPage($parameters->getParameter('pageId'));

		$context->addData('webStoreId', $parameters->getParameter('webStoreId'));
		$context->addData('billingAreaId', $parameters->getParameter('billingAreaId'));
		$context->addData('zone', $parameters->getParameter('zone'));
		$context->addData('conditionId', $parameters->getParameter('conditionId'));

		$context->addData('facetFilters', $parameters->getParameter('facetFilters'));
		$context->addData('searchText', $parameters->getParameter('searchText'));
		$context->addData('sortBy', $parameters->getParameter('sortBy'));
		$context->addData('showUnavailable', ($parameters->getParameter('showUnavailable') == true));
		return $context;
	}
}
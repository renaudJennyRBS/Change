<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResult
 */
class StoreResult extends \Rbs\Catalog\Blocks\ProductList
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
		return $parameters;
	}

	protected function extractText($request)
	{
		$searchText = $request->getQuery('searchText');
		if (!\Change\Stdlib\String::isEmpty($searchText))
		{
			return  trim($searchText);
		}
		return null;
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
		$searchText = $parameters->getParameter('searchText');
		if (!$searchText)
		{
			return null;
		}

		$application = $event->getApplication();
		$logging = $application->getLogging();
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productListId !== null)
		{
			/** @var $productList \Rbs\Catalog\Documents\ProductList|null */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$logging->warn(__METHOD__ . ': invalid product list');
				return null;
			}
		}

		$context = $this->populateContext($application, $documentManager, $parameters);
		if ($productListId)
		{
			$context->addData('listId', $productListId);
		}

		$contextArray = $context->toArray();
		$result = $commerceServices->getCatalogManager()->getProductsData($contextArray);
		$attributes['productsData'] = $result['items'];
		$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');

		$pagination = $result['pagination'];
		$pagination['pageCount'] = $pageCount = ceil($pagination['count'] / $pagination['limit']);
		$pagination['pageNumber'] = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);
		$attributes['pagination'] = $pagination;
		return 'store-result.twig';
	}
}
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
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Elasticsearch\Blocks\Facets
 */
class Facets extends Block
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
		$parameters->addParameterMeta('useCurrentSectionProductList');
		$parameters->addParameterMeta('showUnavailable', true);
		$parameters->addParameterMeta('facets');
		$parameters->addParameterMeta('sortBy');
		$parameters->setNoCache();
		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters->addParameterMeta('facetFilters', null);
		$parameters->addParameterMeta('formAction', null);
		$parameters->addParameterMeta('indexId', null);
		$parameters->addParameterMeta('commerceContext', []);

		$commerceServices = $this->getCommerceServices($event);
		$genericServices = $this->getGenericServices($event);

		/** @var $website \Rbs\Website\Documents\Website */
		$website = $event->getParam('website');
		if ($commerceServices == null || $genericServices == null || !($website instanceof \Rbs\Website\Documents\Website))
		{
			$this->setInvalidParameters($parameters);
			return $parameters;
		}


		$storeIndex = $genericServices->getIndexManager()->getStoreIndexByWebsite($website, $website->getLCID());
		if (!$storeIndex)
		{
			$this->setInvalidParameters($parameters);
			return $parameters;
		}
		$parameters->setParameterValue('indexId', $storeIndex->getId());

		$ctx = $commerceServices->getContext();
		$commerceContext = [];
		if ($ctx->getZone()) {
			$commerceContext['zone'] = $ctx->getZone();
		}
		if ($ctx->getWebStore()) {
			$commerceContext['storeId'] = $ctx->getWebStore()->getId();
		}
		if ($ctx->getBillingArea()) {
			$commerceContext['billingAreaId'] = $ctx->getBillingArea()->getId();
		}
		$parameters->setParameterValue('commerceContext', $commerceContext);

		// Product list.
		$this->setParameterValueForDetailBlock($parameters, $event);
		if ($parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) == null
			&& $parameters->getParameter('useCurrentSectionProductList') === true)
		{
			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$section = $page->getSection();

			$catalogManager = $commerceServices->getCatalogManager();
			$defaultProductList = $catalogManager->getDefaultProductListBySection($section);
			if ($this->isValidDocument($defaultProductList))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $defaultProductList->getId());
			}
			else
			{
				$this->setInvalidParameters($parameters);
				return $parameters;
			}
		}

		$facets = $parameters->getParameterValue('facets');
		if (!is_array($facets))
		{
			$facets = array();
		}

		if (!count($facets) && $parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME))
		{
			$productListId = $parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
			/** @var $productList \Rbs\Catalog\Documents\ProductList */
			$productList = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($productListId, 'Rbs_Catalog_ProductList');
			if ($productList)
			{
				foreach ($productList->getFacets() as $facet)
				{
					$facets[] = $facet->getId();
				}
			}
		}
		$parameters->setParameterValue('facets', $facets);

		$request = $event->getHttpRequest();
		$uri = $event->getUrlManager()->getSelf();
		$queryFilters = $request->getQuery('facetFilters', null);
		$facetFilters = $this->validateQueryFilters($queryFilters);
		$parameters->setParameterValue('facetFilters', $facetFilters);

		$sortBy = $request->getQuery('sortBy-facet');
		if ($sortBy)
		{
			$parameters->setParameterValue('sortBy', $sortBy);
		}

		$query = $uri->getQueryAsArray();
		unset($query['facetFilters']);
		$parameters->setParameterValue('formAction', $uri->setQuery($query)->normalize()->toString());
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
	 * @param Parameters $parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('indexId', 0);
		$parameters->setParameterValue('facets', []);
		$parameters->setParameterValue('facetFilters', []);
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
	 * @param Event $event
	 * @return \Rbs\Generic\GenericServices | null
	 */
	protected function getGenericServices($event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			return null;
		}
		return $genericServices;
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Commerce\CommerceServices
	 */
	protected function getCommerceServices($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			return null;
		}
		return $commerceServices;
	}

	protected $collections = array();

	/**
	 * @param string $collectionCode
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return \Change\Collection\CollectionInterface|null
	 */
	protected function getCollectionByCode($collectionCode, $collectionManager)
	{
		if (!$collectionCode)
		{
			return null;
		}

		if (!array_key_exists($collectionCode, $this->collections))
		{
			$this->collections[$collectionCode] = $collectionManager->getCollection($collectionCode);
		}
		return $this->collections[$collectionCode];
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		$genericServices = $this->getGenericServices($event);
		$commerceServices = $this->getCommerceServices($event);
		if ($genericServices == null || $commerceServices == null)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': services not set');
			return null;
		}

		$parameters = $event->getBlockParameters();

		$indexManager = $genericServices->getIndexManager();

		/** @var $storeIndex \Rbs\Elasticsearch\Documents\StoreIndex */
		$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('indexId'));

		$client = $indexManager->getElasticaClient($storeIndex->getClientName());
		if (!$client)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid client ' . $storeIndex->getClientName());
			return null;
		}

		$index = $client->getIndex($storeIndex->getName());
		if (!$index->exists())
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': index not exist ' . $storeIndex->getName());
			return null;
		}

		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$productList = null;
		if ($productListId !== null)
		{
			/** @var $productList \Rbs\Catalog\Documents\ProductList|null */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$applicationServices->getLogging()->warn(__METHOD__ . ': invalid product list');
				return null;
			}
		}

		$facets = [];
		$facetIds = $parameters->getParameter('facets');

		if (is_array($facetIds) && count($facetIds))
		{
			$facets = $genericServices->getFacetManager()->resolveFacetIds($facetIds);
		}

		if (count($facets))
		{
			$attributes['facets'] = $facets;
			$facetFilters = $parameters->getParameter('facetFilters');
			if (!is_array($facetFilters)) {
				$facetFilters = [];
			}
		}
		else
		{
			return null;
		}

		$context = $parameters->getParameter('commerceContext');

		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($storeIndex, $indexManager, $genericServices->getFacetManager());

		$query = $queryHelper->getProductListQuery($productList);
		$queryHelper->addFilteredFacets($query, $facets, $facetFilters, $context);

		//$event->getApplication()->getLogging()->fatal(json_encode($query->toArray()));
		$result = $index->getType($storeIndex->getDefaultTypeName())->search($query);

		$facetsValues = $queryHelper->formatAggregations($result->getAggregations(), $facets);
		if (count($facetFilters))
		{
			$queryHelper->applyFacetFilters($facetsValues, $facetFilters);
		}
		$attributes['facetValues'] = $facetsValues;

		return 'facets.twig';
	}
}
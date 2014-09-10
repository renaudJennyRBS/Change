<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Blocks;

/**
 * @name \Rbs\Elasticsearch\Blocks\ResultHeader
 */
class ResultHeader extends \Change\Presentation\Blocks\Standard\Block
{
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
		$parameters->addParameterMeta('productResultsPage');
		$parameters->addParameterMeta('otherResultsPage');
		$parameters->addParameterMeta('allowedSectionIds');
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$request = $event->getHttpRequest();
		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', trim($searchText));
		}

		$genericServices = $this->getGenericServices($event);

		/** @var $website \Rbs\Website\Documents\Website */
		$website = $event->getParam('website');
		if ($genericServices == null || !($website instanceof \Rbs\Website\Documents\Website))
		{
			$this->setInvalidParameters($parameters);
			return $parameters;
		}

		$fulltextIndex = $genericServices->getIndexManager()->getFulltextIndexByWebsite($website, $website->getLCID());
		if (!$fulltextIndex)
		{
			$this->setInvalidParameters($parameters);
			return $parameters;
		}
		$parameters->setParameterValue('fulltextIndex', $fulltextIndex->getId());

		/* @var $page \Rbs\Website\Documents\Page */
		$page = $event->getParam('page');
		$parameters->setParameterValue('currentPageId', $page->getId());

		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('fulltextIndex', 0);
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

		$fullTextIndexId = $parameters->getParameter('fulltextIndex');
		$genericServices = $this->getGenericServices($event);
		if (!$genericServices || !$fullTextIndexId)
		{
			return null;
		}

		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		/** @var $fullTextIndex \Rbs\Elasticsearch\Documents\FullText */
		$fullTextIndex = $documentManager->getDocumentInstance($fullTextIndexId, 'Rbs_Elasticsearch_FullText');
		if (!$fullTextIndex)
		{
			return null;
		}
		$searchText = $parameters->getParameter('searchText');
		$allowedSectionIds = $parameters->getParameter('allowedSectionIds');

		$indexManager = $genericServices->getIndexManager();
		$client = $indexManager->getElasticaClient($fullTextIndex->getClientName());
		if (!$client)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid client ' . $fullTextIndex->getClientName());
			return null;
		}

		$index = $client->getIndex($fullTextIndex->getName());
		if (!$index->exists())
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': index not exist ' . $fullTextIndex->getName());
			return null;
		}

		$facetFilters = $parameters->getParameter('facetFilters');
		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($fullTextIndex, $indexManager, $genericServices->getFacetManager());
		$query = $queryHelper->getSearchQuery($searchText, $allowedSectionIds);
		$queryHelper->addHighlight($query);

		$facets = $fullTextIndex->getFacetsDefinition();
		if (is_array($facetFilters) && count($facetFilters))
		{
			$filter = $queryHelper->getFacetsFilter($facets, $facetFilters, []);
			if ($filter)
			{
				$query->setFilter($filter);
			}
		}
		$queryHelper->addFacets($query, $facets);

		$resultCounts = [ 'total' => 0, 'products' => 0, 'others' => 0 ];

		$query->setSize(0);
		$searchResult = $index->getType($fullTextIndex->getDefaultTypeName())->search($query);
		if ($searchResult)
		{
			$facetValues = $queryHelper->formatAggregations($searchResult->getAggregations(), $facets);
			if (is_array($facetFilters) && count($facetFilters))
			{
				$queryHelper->applyFacetFilters($facetValues, $facetFilters);
			}

			$facet = $facetValues[0];
			$attributes['facet'] = $facet;
			foreach ($facet->getValues() as $value)
			{
				if ($value->getKey() == 'Rbs_Catalog_Product')
				{
					$resultCounts['products'] += $value->getValue();
				}
				else
				{
					$resultCounts['others'] += $value->getValue();
				}
				$resultCounts['total'] += $value->getValue();
			}
		}
		$attributes['resultCounts'] = $resultCounts;

		$attributes['isProductPage'] = $parameters->getParameterValue('productResultsPage') == $parameters->getParameterValue('currentPageId');
		$attributes['isOtherPage'] = $parameters->getParameterValue('otherResultsPage') == $parameters->getParameterValue('currentPageId');

		return 'result-header.twig';
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Rbs\Generic\GenericServices
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
}
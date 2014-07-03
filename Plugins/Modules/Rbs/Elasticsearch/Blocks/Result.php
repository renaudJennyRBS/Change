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
 * @name \Rbs\Elasticsearch\Blocks\Result
 */
class Result extends Block
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
		$parameters->addParameterMeta('searchText');
		$parameters->addParameterMeta('showModelFacet', true);
		$parameters->addParameterMeta('allowedSectionIds');
		$parameters->addParameterMeta('itemsPerPage', 20);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('facetFilters', null);
		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

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


		$request = $event->getHttpRequest();
		$queryFilters = $request->getQuery('facetFilters', null);
		$facetFilters = array();
		if (is_array($queryFilters)) {
			foreach ($queryFilters as $fieldName => $rawValue)
			{
				if (is_string($fieldName) && $rawValue)
				{
					$facetFilters[$fieldName] = $rawValue;
				}
			}
		}
		$parameters->setParameterValue('facetFilters', $facetFilters);

		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', $searchText);
			$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		}
		return $parameters;
	}

	/**
	 * @param Event $event
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
	/**
	 * @param Parameters $parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('fulltextIndex', 0);
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
		$searchText = trim($parameters->getParameter('searchText'), '');
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

		$showModelFacet = $parameters->getParameter('showModelFacet');
		if ($showModelFacet)
		{
			$facetFilters = $parameters->getParameter('facetFilters');
		}
		else
		{
			$facetFilters = null;
		}
		$showResult = !empty($searchText);
		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($fullTextIndex, $indexManager, $genericServices->getFacetManager());
		$query = $queryHelper->getSearchQuery($searchText, $allowedSectionIds);
		$queryHelper->addHighlight($query);
		if ($showModelFacet)
		{
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
		}
		else
		{
			$facets = null;
		}
		$searchResult = null;
		$attributes['items'] = [];
		$attributes['showResult'] = $showResult;

		if ($showResult)
		{
			$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
			$size = $parameters->getParameter('itemsPerPage');
			$from = ($pageNumber - 1) * $size;
			$query->setFrom($from)->setSize($size);

			$searchResult = $index->getType($fullTextIndex->getDefaultTypeName())->search($query);
			$attributes['totalCount'] = $searchResult->getTotalHits();
			if ($attributes['totalCount'])
			{
				$maxScore = $searchResult->getMaxScore();
				$attributes['pageCount'] = ceil($attributes['totalCount'] / $size);
				$i18nManager = $applicationServices->getI18nManager();

				/* @var $result \Elastica\Result */
				foreach ($searchResult->getResults() as $result)
				{
					$score = ceil(($result->getScore() / $maxScore) * 100);
					$document = $documentManager->getDocumentInstance($result->getId());
					if ($document instanceof \Change\Documents\Interfaces\Publishable && $document->published())
					{
						$highlights = $result->getHighlights();
						if (isset($highlights['title']))
						{
							$title = $highlights['title'][0];
						}
						else
						{
							$title = $i18nManager->transformHtml($result->title, $i18nManager->getLCID());
						}
						$attributes['items'][] =['id' => $result->getId(), 'score' => $score,
							'title' => $title,'document' => $document,
							'content' => isset($highlights['content']) ? $highlights['content'] : []
						];
					}
				}
			}
		}
		elseif ($showModelFacet)
		{
			$query->setSize(0);
			$searchResult = $index->getType($fullTextIndex->getDefaultTypeName())->search($query);
		}

		if ($showModelFacet && $searchResult)
		{
			$facetValues = $queryHelper->formatAggregations($searchResult->getAggregations(), $facets);
			if (is_array($facetFilters) && count($facetFilters))
			{
				$queryHelper->applyFacetFilters($facetValues, $facetFilters);
			}
			$attributes['facet'] = $facetValues[0];
		}
		return 'result.twig';
	}
}
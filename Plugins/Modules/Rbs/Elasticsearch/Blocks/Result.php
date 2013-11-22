<?php
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
		$parameters->addParameterMeta('fulltextIndex');
		$parameters->addParameterMeta('websiteId');
		$parameters->addParameterMeta('allowedSectionIds');
		$parameters->addParameterMeta('itemsPerPage', 20);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('facetFilters', null);
		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());
		$fulltextIndexId = $parameters->getParameter('fulltextIndex');
		if (is_array($fulltextIndexId))
		{
			$fulltextIndexId =  isset($fulltextIndexId['id']) ? $fulltextIndexId['id'] : null;
		}


		$fullTextIndex = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($fulltextIndexId);
		if ($fullTextIndex instanceof \Rbs\Elasticsearch\Documents\FullText && $fullTextIndex->activated())
		{
			$websiteId = $fullTextIndex->getWebsiteId();
			$allowedSectionIds = $event->getPermissionsManager()->getAllowedSectionIds($websiteId);
			$parameters->setParameterValue('websiteId', $websiteId);
			$parameters->setParameterValue('allowedSectionIds', $allowedSectionIds);
		}
		else
		{
			$fulltextIndexId = null;
		}
		$parameters->setParameterValue('fulltextIndex', $fulltextIndexId);
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
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$genericServices = $this->getGenericServices($event);
		if (!$genericServices)
		{
			return null;
		}
		$applicationServices = $event->getApplicationServices();
		$parameters = $event->getBlockParameters();
		$fullTextIndex = $applicationServices->getDocumentManager()->getDocumentInstance($parameters->getParameter('fulltextIndex'));
		if ($fullTextIndex instanceof \Rbs\Elasticsearch\Documents\FullText && $fullTextIndex->activated())
		{
			$searchText = trim($parameters->getParameter('searchText'), '');
			$allowedSectionIds = $parameters->getParameter('allowedSectionIds');
			$facetFilters = $parameters->getParameter('facetFilters');

			$genericServices = $this->getGenericServices($event);
			$indexManager = $genericServices->getIndexManager();

			$client = $indexManager->getClient($fullTextIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($fullTextIndex->getName());
				if ($index->exists())
				{
					$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($fullTextIndex);
					$searchQuery->setFacetManager($genericServices->getFacetManager());
					$searchQuery->setI18nManager($applicationServices->getI18nManager());
					$searchQuery->setCollectionManager($applicationServices->getCollectionManager());

					if ($searchText || (is_array($facetFilters) && count($facetFilters)))
					{
						$attributes['items'] = array();
						$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
						$size = $parameters->getParameter('itemsPerPage');
						$from = ($pageNumber - 1) * $size;

						$query = $searchQuery->getSearchQuery($searchText, $allowedSectionIds, null, $from, $size, array('model', 'title'));
						$searchQuery->addFacets($query, array('model'));
						$bool = $searchQuery->getFacetFilters($facetFilters);
						if ($bool)
						{
							$query->setFilter($bool);
						}

						$searchResult = $index->getType($fullTextIndex->getDefaultTypeName())->search($query);
						$attributes['totalCount'] = $searchResult->getTotalHits();
						if ($attributes['totalCount'])
						{
							$maxScore = $searchResult->getMaxScore();
							$attributes['pageCount'] = ceil($attributes['totalCount'] / $size);
							/* @var $result \Elastica\Result */
							foreach ($searchResult->getResults() as $result)
							{
								$score = ceil(($result->getScore() / $maxScore) * 100);
								$attributes['items'][] = array('id' => $result->getId(), 'score' => $score, 'title' => $result->title);
							}
						}
						$facetValues = $searchQuery->buildFacetValues($searchResult->getFacets(), $facetFilters, array('model'));
					}
					else
					{
						$attributes['items'] = false;
						$query = $searchQuery->getSearchQuery(null, $allowedSectionIds, null, 0, 0);
						$searchQuery->addFacets($query,  array('model'));
						$searchResult = $index->getType($fullTextIndex->getDefaultTypeName())->search($query);
						$facetValues = $searchQuery->buildFacetValues($searchResult->getFacets(), $facetFilters, array('model'));
					}
					$attributes['facet'] = isset($facetValues['model']) ? $facetValues['model'] : array();
				}
			}
			return 'result.twig';
		}
		return null;
	}
}
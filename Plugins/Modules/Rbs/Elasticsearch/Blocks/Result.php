<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
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
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('facetFilters', null);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$fulltextIndexId = $parameters->getParameter('fulltextIndex');
		if (is_array($fulltextIndexId))
		{
			$fulltextIndexId =  isset($fulltextIndexId['id']) ? $fulltextIndexId['id'] : null;
		}

		$fullTextIndex = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($fulltextIndexId);
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
		$facetFilters = $request->getQuery('facetFilters', null);
		if (is_array($facetFilters)) {
			$facetFilters = array_filter($facetFilters, function($filter) {return is_string($filter) && !empty($filter);});
			if (count($facetFilters))
			{
				$parameters->setParameterValue('facetFilters', $facetFilters);
			}
		}

		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', $searchText);
			$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$documentServices = $event->getDocumentServices();
		$parameters = $event->getBlockParameters();
		$fullTextIndex = $documentServices->getDocumentManager()->getDocumentInstance($parameters->getParameter('fulltextIndex'));
		if ($fullTextIndex instanceof \Rbs\Elasticsearch\Documents\FullText && $fullTextIndex->activated())
		{

			$searchText = trim($parameters->getParameter('searchText'), '');
			$allowedSectionIds = $parameters->getParameter('allowedSectionIds');
			$facetFilters = $parameters->getParameter('facetFilters');

			$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
			$indexManager->setDocumentServices($event->getDocumentServices());
			$client = $indexManager->getClient($fullTextIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($fullTextIndex->getName());
				if ($index->exists())
				{
					if ($searchText || is_array($facetFilters))
					{
						$attributes['items'] = array();
						$query = $this->buildQuery($searchText, $allowedSectionIds);
						$this->addFacets($query, $facetFilters);

						$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
						$size = $parameters->getParameter('itemsPerPage');
						$from = ($pageNumber - 1) * $size;
						$query->setFrom($from);
						$query->setSize($size);

						$searchResult = $index->getType('document')->search($query);
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
						$attributes['facet'] = $this->getFacetAttribute($searchResult->getFacets(), $facetFilters, $documentServices);
					}
					else
					{
						$attributes['items'] = false;
						$query = $this->buildQuery(null, $allowedSectionIds);
						$this->addFacets($query, $facetFilters);
						$query->setFrom(0);
						$query->setSize(0);
						$searchResult = $index->getType('document')->search($query);
						$attributes['facet'] = $this->getFacetAttribute($searchResult->getFacets(), $facetFilters, $documentServices);
					}
				}
			}
			return 'result.twig';
		}
		return null;
	}

	/**
	 * @param array $facets
	 * @param array $facetFilters
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return \Rbs\Elasticsearch\Std\FacetTermValue[]
	 */
	protected function getFacetAttribute($facets, $facetFilters, $documentServices)
	{
		$facetValues = array();
		if (is_array($facets) && isset($facets['model']))
		{
			foreach ($facets['model']['terms'] as $term)
			{
				$modelName = $term['term'];
				$v = new \Rbs\Elasticsearch\Std\FacetTermValue($modelName);
				$v->setCount(intval($term['count']));
				if (is_array($facetFilters) && in_array($modelName, $facetFilters))
				{
					$v->setFiltered(true);
				}
				$model = $documentServices->getModelManager()->getModelByName($modelName);
				if ($model)
				{
					$v->setValueTitle($documentServices->getApplicationServices()->getI18nManager()->trans($model->getLabelKey()));
				}
				$facetValues[] = $v;
			}
		}
		return $facetValues;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param $facetFilters
	 */
	protected function addFacets($query, $facetFilters)
	{
		$facet = new \Elastica\Facet\Terms('model');
		$facet->setField('model');
		$query->addFacet($facet);

		if (is_array($facetFilters))
		{
			$terms = array();
			foreach ($facetFilters as $facetFilter)
			{
				$terms[] = $facetFilter;
			}

			if (count($terms))
			{
				$bool = new \Elastica\Filter\Bool();
				$bool->addMust(new \Elastica\Filter\Terms('model', $terms));
				$query->setFilter($bool);
			}
		}
	}


	protected function getFacetFilter()
	{

	}

	/**
	 * @param integer $pageNumber
	 * @param integer $pageCount
	 * @return integer
	 */
	protected function fixPageNumber($pageNumber, $pageCount)
	{
		if (!is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > $pageCount)
		{
			return 1;
		}
		return $pageNumber;
	}

	/**
	 * @param string $searchText
	 * @param integer[] $allowedSectionIds
	 * @return \Elastica\Query
	 */
	protected function buildQuery($searchText, $allowedSectionIds)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		if ($searchText)
		{
			$multiMatch = new \Elastica\Query\MultiMatch();
			$multiMatch->setQuery($searchText);
			$multiMatch->setFields(array('title', 'content'));
		}
		else
		{
			$multiMatch = new \Elastica\Query\MatchAll();
		}

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
		}
		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);

		$query = new \Elastica\Query($filtered);
		$query->setFields(array('model', 'title'));

		return $query;
	}


	/*
{"query":{"filtered":{"query":{"match_all":{}},"filter":{"bool":{"must":[{"range":{"startPublication":{"lte":"2013-10-16T08:07:05+0200"}}},{"range":{"endPublication":{"gt":"2013-10-16T08:07:05+0200"}}}]}}}},"fields":["model","title"],
"filter":{"bool":{"must":[{"terms":{"model":["Rbs_Website_Topic", "Rbs_Website_Website"]}}]}},
"facets":{"model":{"terms":{"field":"model"}}},"from":0,"size":50}
	 */
}
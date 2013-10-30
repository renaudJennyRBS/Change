<?php
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
		$parameters->addParameterMeta('facetGroups');
		$parameters->addParameterMeta('facetFilters', null);
		$parameters->addParameterMeta('formAction', null);
		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$uri = $event->getUrlManager()->getSelf();
		$queryFilters = $request->getQuery('facetFilters', null);
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
		}
		$parameters->setParameterValue('facetFilters', $facetFilters);

		$query = $uri->getQueryAsArray();
		unset($query['facetFilters']);
		$parameters->setParameterValue('formAction', $uri->setQuery($query)->normalize()->toString());
		return $parameters;
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Elasticsearch\ElasticsearchServices
	 */
	protected function getElasticsearchServices($event)
	{
		$elasticsearchServices = $event->getServices('elasticsearchServices');
		if (!($elasticsearchServices instanceof \Rbs\Elasticsearch\ElasticsearchServices))
		{
			$applicationServices = $event->getDocumentServices()->getApplicationServices();
			$elasticsearchServices = new \Rbs\Elasticsearch\ElasticsearchServices($applicationServices, $event->getDocumentServices());
			$event->getServices()->set('elasticsearchServices', $elasticsearchServices);
		}
		return $elasticsearchServices;
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
		$documentServices = $event->getDocumentServices();
		$parameters = $event->getBlockParameters();

		/* @var $commonServices \Change\Services\CommonServices */
		$commonServices = $event->getServices('commonServices');

		$facetGroupIds = $parameters->getParameter('facetGroups');
		$facetGroups = array();
		$storeIndexId = null;
		$facets = array();
		foreach ($facetGroupIds as $facetGroupId)
		{
			$group = $documentServices->getDocumentManager()->getDocumentInstance($facetGroupId);
			if ($group instanceof \Rbs\Elasticsearch\Documents\FacetGroup)
			{
				if ($storeIndexId == null)
				{
					$storeIndexId = $group->getIndexId();
				}
				if ($storeIndexId && $storeIndexId == $group->getIndexId())
				{
					foreach ($group->getFacets() as $facet)
					{
						if ($facet->getIndexId() == $storeIndexId)
						{
							$facets[$facet->getFieldName()] = $facet;
						}
					}
					$facetGroups[] = $group;
				}
			}
		}

		$storeIndex = $documentServices->getDocumentManager()->getDocumentInstance($storeIndexId);
		if ($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
		{
			$attributes['facetGroups'] = $facetGroups;
			$elasticsearchServices = $this->getElasticsearchServices($event);
			$facetManager = $elasticsearchServices->getFacetManager();
			$facetManager->setCollectionManager($commonServices->getCollectionManager());

			$client = $elasticsearchServices->getIndexManager()->getClient($storeIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($storeIndex->getName());
				if ($index->exists())
				{
					$query = $this->buildQuery(null, null);
					$this->addFacets($query, $facets, $commonServices);
					$result = $index->getType('product')->search($query);
					$attributes['facetValues'] = $this->buildFacetValues($result->getFacets(),
						$parameters->getParameter('facetFilters'), $facets, $facetManager);
					return 'facets.twig';
				}
			}
		}
		return null;
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
		$query->setSize(0);
		return $query;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param \Rbs\Elasticsearch\Documents\Facet[] $facets
	 * @param \Change\Services\CommonServices $commonServices
	 */
	protected function addFacets($query, $facets, $commonServices)
	{
		foreach ($facets as $facet)
		{
			if ($facet->getFacetType() === \Rbs\Elasticsearch\Facet\FacetDefinitionInterface::TYPE_RANGE)
			{
				$collection = $this->getCollectionByCode($facet->getCollectionCode(), $commonServices->getCollectionManager());
				if (!$collection)
				{
					continue;
				}
				$ranges = array();
				foreach ($collection->getItems() as $item)
				{
					$fromTo = explode('::', $item->getValue());
					if (count($fromTo) == 2)
					{
						$ranges[] = $fromTo;
					}
				}
				if (count($ranges))
				{
					$queryFacet = new \Elastica\Facet\Range($facet->getFieldName());
					$queryFacet->setField($facet->getFieldName());
					foreach ($ranges as $fromTo)
					{
						$queryFacet->addRange($fromTo[0] == '' ? null : $fromTo[0], $fromTo[1] == '' ? null : $fromTo[1]);
					}
					$query->addFacet($queryFacet);
				}
			}
			else
			{
				$queryFacet = new \Elastica\Facet\Terms($facet->getFieldName());
				$queryFacet->setField($facet->getFieldName());
				$query->addFacet($queryFacet);
			}
		}
	}

	/**
	 * @param array $facetResults
	 * @param array $facetFilters
	 * @param \Rbs\Elasticsearch\Documents\Facet[] $facets
	 * @param \Rbs\Elasticsearch\Facet\FacetManager $facetManager
	 * @return \Rbs\Elasticsearch\Facet\FacetValue[]
	 */
	protected function buildFacetValues($facetResults, $facetFilters, $facets, $facetManager)
	{
		$facetValues = array();
		if (is_array($facetResults))
		{
			foreach ($facetResults as $facetName => $facetData)
			{
				$facet = isset($facets[$facetName]) ? $facets[$facetName] : null;
				if (!$facet)
				{
					continue;
				}
				if ($facetData['_type'] == 'terms')
				{
					foreach ($facetData['terms'] as $term)
					{
						if (($count = intval($term['count'])) == 0 && !$facet->getShowEmptyItem())
						{
							continue;
						}
						$value = $term['term'];
						$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
						$facetValue->setCount($count);
						if (is_array($facetFilters) && isset($facetFilters[$facetName]))
						{
							$facetFilter = $facetFilters[$facetName];
							if ((is_array($facetFilter) && in_array($value, $facetFilter))
								|| (is_string($facetFilter) && $facetFilter == $value)
							)
							{
								$facetValue->setFiltered(true);
							}
						}
						$facetValues[$facetName][] = $facetManager->updateFacetValueTitle($facetValue, $facet);
					}
				}
				else
				{
					foreach ($facetData['ranges'] as $range)
					{
						if (($count = intval($range['count'])) == 0 && !$facet->getShowEmptyItem())
						{
							continue;
						}
						$value = (isset($range['from']) ? $range['from'] : '') . '::' . (isset($range['to']) ? $range['to'] : '');
						$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
						$facetValue->setCount(intval($range['count']));
						if (is_array($facetFilters) && isset($facetFilters[$facetName]))
						{
							$facetFilter = $facetFilters[$facetName];
							if ((is_array($facetFilter) && in_array($value, $facetFilter))
								|| (is_string($facetFilter) && $facetFilter == $value)
							)
							{
								$facetValue->setFiltered(true);
							}
						}
						$facetValues[$facetName][] = $facetManager->updateFacetValueTitle($facetValue, $facet);
					}
				}
			}
		}
		return $facetValues;
	}
}
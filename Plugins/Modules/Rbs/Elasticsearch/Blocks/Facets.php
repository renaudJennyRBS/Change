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
	 * @return \Rbs\Elasticsearch\ElasticsearchServices|null
	 */
	protected function getElasticsearchServices($event)
	{
		$elasticsearchServices = $event->getServices('elasticsearchServices');
		if (!($elasticsearchServices instanceof \Rbs\Elasticsearch\ElasticsearchServices))
		{
			return null;
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
		$applicationServices = $event->getApplicationServices();
		$parameters = $event->getBlockParameters();

		$facetGroupIds = $parameters->getParameter('facetGroups');
		$facetGroups = array();
		$storeIndexId = null;
		$facets = array();
		foreach ($facetGroupIds as $facetGroupId)
		{
			$group = $applicationServices->getDocumentManager()->getDocumentInstance($facetGroupId);
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

		$storeIndex = $applicationServices->getDocumentManager()->getDocumentInstance($storeIndexId);
		if ($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
		{
			$attributes['facetGroups'] = $facetGroups;
			$elasticsearchServices = $this->getElasticsearchServices($event);
			if (!$elasticsearchServices)
			{
				return null;
			}
			$client = $elasticsearchServices->getIndexManager()->getClient($storeIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($storeIndex->getName());
				if ($index->exists())
				{
					$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($elasticsearchServices, $storeIndex);
					$facetFilters = $parameters->getParameter('facetFilters');
					$query = $searchQuery->getFacetsQuery(null, null, $facetFilters, $facets);
					$result = $index->getType($storeIndex->getDefaultTypeName())->search($query);
					$attributes['facetValues'] = $searchQuery->buildFacetValues($result->getFacets(),
						$facetFilters, $facets);
					return 'facets.twig';
				}
			}
		}
		return null;
	}
}
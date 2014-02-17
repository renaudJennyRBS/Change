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
		$parameters->addParameterMeta('productListId');
		$parameters->addParameterMeta('sortBy');
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

		$sortBy = $request->getQuery('sortBy');
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
	 * @param Event $event
	 * @return \Rbs\Generic\GenericServices|null
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
		if ($genericServices == null)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': genericServices not defined');
			return null;
		}

		$parameters = $event->getBlockParameters();

		$productListId = $parameters->getParameter('productListId');
		/** @var $productList \Rbs\Catalog\Documents\ProductList|null */
		$productList = null;
		if ($productListId !== null)
		{
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$applicationServices->getLogging()->warn(__METHOD__ . ': invalid product list');
				return null;
			}
		}

		if ($productList !== null)
		{
			// Get facets groups
			$groups = $productList->getFacetGroups();
			$storeIndexId = null;
			$facetGroups = array();
			$facets = array();
			foreach ($groups as $group)
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
			$attributes['facetGroups'] = $facetGroups;
		}
		else
		{
			return null;
		}

		// Verify Index
		$storeIndex = $documentManager->getDocumentInstance($storeIndexId);
		if (!($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex))
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid store index');
			return null;
		}

		$client = $genericServices->getIndexManager()->getClient($storeIndex->getClientName());
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

		// Create Query
		$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($storeIndex);
		$searchQuery->setFacetManager($genericServices->getFacetManager());
		$searchQuery->setI18nManager($applicationServices->getI18nManager());
		$searchQuery->setCollectionManager($applicationServices->getCollectionManager());
		$facetFilters = $parameters->getParameter('facetFilters');
		if (is_array($facetFilters) && count($facetFilters))
		{
			$type = $index->getType($storeIndex->getDefaultTypeName());
			$facetResults = array();
			foreach ($facets as $facet)
			{
				$query = $searchQuery->getListFacetQuery($productList, $facet, $facetFilters);
				if ($query)
				{
					$facetResults = array_merge($facetResults, $type->search($query)->getFacets());
				}
			}
			$attributes['facetValues'] = $searchQuery->buildFacetValues($facetResults, $facetFilters, $facets);
		}
		else
		{
			$query = $searchQuery->getListFacetsQuery($productList, $facets);
			$facetResults = $index->getType($storeIndex->getDefaultTypeName())->search($query)->getFacets();
			$attributes['facetValues'] = $searchQuery->buildFacetValues($facetResults, $facetFilters, $facets);
		}
		return 'facets.twig';
	}
}
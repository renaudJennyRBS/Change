<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResult
 */
class StoreResult extends Block
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
		$parameters->addParameterMeta('facetFilters', null);
		$parameters->addParameterMeta('searchText');
		$parameters->addParameterMeta('storeIndex');
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('webStoreId', null);

		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);

		$parameters->addParameterMeta('displayPrices', true);
		$parameters->addParameterMeta('displayPricesWithTax', true);

		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', $searchText);
		}
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

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

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$documentServices = $event->getDocumentServices();
		$documentManager = $documentServices->getDocumentManager();

		/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$parameters = $event->getBlockParameters();
		$searchText = trim($parameters->getParameter('searchText'), '');
		$facetFilters = $parameters->getParameter('facetFilters');

		$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('storeIndex'));
		if ($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
		{
			$parameters->setParameterValue('webStoreId', $storeIndex->getStoreId());
			$elasticsearchServices = $this->getElasticsearchServices($event);
			$client = $elasticsearchServices->getIndexManager()->getClient($storeIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($storeIndex->getName());
				if ($index->exists())
				{
					$query = $this->buildQuery($searchText, null);
					$this->addFacetFilters($query, $facetFilters, $storeIndex);

					$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
					$size = $parameters->getParameter('itemsPerPage');
					$from = ($pageNumber - 1) * $size;
					$query->setFrom($from);
					$query->setSize($size);

					$searchResult = $index->getType('product')->search($query);
					$attributes['totalCount'] = $totalCount =  $searchResult->getTotalHits();

					$rows = array();
					if ($totalCount)
					{
						$itemsPerPage = $parameters->getParameter('itemsPerPage');
						$pageCount = ceil($totalCount / $itemsPerPage);
						$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

						$attributes['pageNumber'] = $pageNumber;
						$attributes['totalCount'] = $totalCount;
						$attributes['pageCount'] = $pageCount;

						$webStoreId = $storeIndex->getStoreId();

						/* @var $result \Elastica\Result */
						foreach ($searchResult->getResults() as $result)
						{
							$product = $documentManager->getDocumentInstance($result->getId());
							if (!($product instanceof \Rbs\Catalog\Documents\Product))
							{
								continue;
							}
							$url = $event->getUrlManager()->getCanonicalByDocument($product)->toString();

							$row = array('id' => $product->getId(), 'url' => $url);
							$visual = $product->getFirstVisual();
							$row['visual'] = $visual ? $visual->getPath() : null;

							$productPresentation = $product->getPresentation($commerceServices, $webStoreId);
							if ($productPresentation)
							{
								$productPresentation->evaluate();
								$row['productPresentation'] = $productPresentation;
							}

							$rows[] = (new \Rbs\Catalog\Std\ProductItem($row))->setDocumentManager($documentManager);
						}
					}
					$attributes['rows'] = $rows;
				}
			}
			$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');
			return 'store-result.twig';
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
		$query->setFields(array('model'));
		return $query;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param $facetFilters
	 * @param \Rbs\Elasticsearch\Documents\StoreIndex $storeIndex
	 */
	protected function addFacetFilters($query, $facetFilters, $storeIndex)
	{
		if (is_array($facetFilters))
		{
			$must = array();
			foreach ($facetFilters as $facetName => $facetFilter)
			{
				if (is_string($facetFilter) &&  empty($facetFilter))
				{
					continue;
				}
				foreach ($storeIndex->getFacets() as $facet)
				{
					if ($facet->getFieldName() == $facetName)
					{
						if ($facet->getFacetType() === \Rbs\Elasticsearch\Facet\FacetDefinitionInterface::TYPE_TERM)
						{
							$must[] = new \Elastica\Filter\Terms($facetName, is_array($facetFilter) ? $facetFilter : array($facetFilter));
						}
						elseif (is_string($facetFilter))
						{
							$fromTo = explode('::', $facetFilter);
							if (count($fromTo) === 2)
							{
								$args = array();
								if ($fromTo[0]){
									$args['from'] = $fromTo[0];
								}
								if ($fromTo[1]){
									$args['to'] = $fromTo[1];
								}
								$must[] = new \Elastica\Filter\Range($facetName, $args);
							}
						}
					}
				}
			}
			if (count($must))
			{
				$bool = new \Elastica\Filter\Bool();
				foreach ($must as $m)
				{
					$bool->addMust($m);
				}
				$query->setFilter($bool);
			}
		}
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
}
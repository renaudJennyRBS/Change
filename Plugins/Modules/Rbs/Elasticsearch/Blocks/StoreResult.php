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
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();


		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$parameters = $event->getBlockParameters();
		$searchText = trim($parameters->getParameter('searchText'), '');
		$facetFilters = $parameters->getParameter('facetFilters');

		$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('storeIndex'));
		if ($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
		{
			$parameters->setParameterValue('webStoreId', $storeIndex->getStoreId());
			$genericServices = $this->getGenericServices($event);
			$client = $genericServices->getIndexManager()->getClient($storeIndex->getClientName());
			if ($client)
			{
				$index = $client->getIndex($storeIndex->getName());
				if ($index->exists())
				{
					$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($storeIndex);
					$searchQuery->setFacetManager($genericServices->getFacetManager());
					$searchQuery->setI18nManager($applicationServices->getI18nManager());
					$searchQuery->setCollectionManager($applicationServices->getCollectionManager());

					$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
					$size = $parameters->getParameter('itemsPerPage');
					$from = ($pageNumber - 1) * $size;
					$query = $searchQuery->getSearchQuery($searchText, null, $facetFilters, $from, $size, array('title'));

					$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);
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
							if (!($product instanceof \Rbs\Catalog\Documents\Product) || !$product->published())
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

							$rows[] = (new \Rbs\Catalog\Product\ProductItem($row))->setDocumentManager($documentManager);
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
}
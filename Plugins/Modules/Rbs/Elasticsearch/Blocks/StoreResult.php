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
		$parameters->addParameterMeta('storeIndex');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('webStoreId', null);

		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);

		$parameters->addParameterMeta('displayPrices', true);
		$parameters->addParameterMeta('displayPricesWithTax', true);

		$parameters->addParameterMeta('productListId');
		$parameters->addParameterMeta('redirectUrl');

		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

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

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$oldValue = $urlManager->getAbsoluteUrl();
			$urlManager->setAbsoluteUrl(true);
			$uri = $urlManager->getByFunction('Rbs_Commerce_Cart');
			if ($uri)
			{
				$parameters->setParameterValue('redirectUrl', $uri->normalize()->toString());
			}
			$urlManager->setAbsoluteUrl($oldValue);
		}

		return $parameters;
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

		$commerceServices = $this->getCommerceServices($event);
		$genericServices = $this->getGenericServices($event);
		if ($commerceServices == null || $genericServices == null)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': commerceServices or genericServices not defined');
			return null;
		}

		$parameters = $event->getBlockParameters();
		$productListId = $parameters->getParameter('productListId');
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

		$facetFilters = $parameters->getParameter('facetFilters');
		$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('storeIndex'));
		if (!($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex))
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid store index');
			return null;
		}

		$parameters->setParameterValue('webStoreId', $storeIndex->getStoreId());

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

		$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($storeIndex);
		$searchQuery->setFacetManager($genericServices->getFacetManager());
		$searchQuery->setI18nManager($applicationServices->getI18nManager());
		$searchQuery->setCollectionManager($applicationServices->getCollectionManager());

		$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
		$size = $parameters->getParameter('itemsPerPage');
		$from = ($pageNumber - 1) * $size;
		$query = $searchQuery->getListSearchQuery($productList, $facetFilters, $from, $size, []);

		$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);
		$attributes['totalCount'] = $totalCount = $searchResult->getTotalHits();

		$rows = array();
		if ($totalCount)
		{
			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$section = $page->getSection();

			$itemsPerPage = $parameters->getParameter('itemsPerPage');
			$pageCount = ceil($totalCount / $itemsPerPage);
			$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

			$attributes['pageNumber'] = $pageNumber;
			$attributes['totalCount'] = $totalCount;
			$attributes['pageCount'] = $pageCount;

			$webStoreId = $storeIndex->getStoreId();
			$contextualUrls = $parameters->getParameter('contextualUrls');

			/* @var $result \Elastica\Result */
			foreach ($searchResult->getResults() as $result)
			{
				$product = $documentManager->getDocumentInstance($result->getId());
				if (!($product instanceof \Rbs\Catalog\Documents\Product) || !$product->published())
				{
					continue;
				}
				if ($contextualUrls)
				{
					$url = $event->getUrlManager()->getByDocument($product, $section)->toString();
				}
				else
				{
					$url = $event->getUrlManager()->getCanonicalByDocument($product)->toString();
				}

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

		$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');
		return 'store-result.twig';
	}
}
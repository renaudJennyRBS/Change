<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\ProductList
 */
class ProductList extends Block
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
		$parameters->addParameterMeta('productListId');
		$parameters->addParameterMeta('conditionId');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->addParameterMeta('redirectUrl');

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('productListId') !== null)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$productList = $documentManager->getDocumentInstance($parameters->getParameter('productListId'));
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$parameters->setParameterValue('productListId', null);
			}
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPrices') === null)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$oldValue = $urlManager->getAbsoluteUrl();
			$urlManager->setAbsoluteUrl(true);
			$parameters->setParameterValue('redirectUrl', $urlManager->getByFunction('Rbs_Commerce_Cart')->normalize()->toString());
			$urlManager->setAbsoluteUrl($oldValue);
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
		$parameters = $event->getBlockParameters();
		$productListId = $parameters->getParameter('productListId');
		if ($productListId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $productList \Rbs\Catalog\Documents\ProductList */
			$productList = $documentManager->getDocumentInstance($productListId);
			$attributes['productList'] = $productList;

			$conditionId = $parameters->getParameter('conditionId');
			$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
			$query->andPredicates($query->published());
			$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
			$subQuery->andPredicates(
				$subQuery->eq('productList', $productListId),
				$subQuery->eq('condition', $conditionId ? $conditionId : 0),
				$subQuery->activated()
			);
			$subQuery->addOrder('position', true);
			$query->addOrder($productList->getProductSortOrder(), $productList->getProductSortDirection());

			$rows = array();
			$totalCount = $query->getCountDocuments();
			if ($totalCount)
			{
				$itemsPerPage = $parameters->getParameter('itemsPerPage');
				$pageCount = ceil($totalCount / $itemsPerPage);
				$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

				$attributes['pageNumber'] = $pageNumber;
				$attributes['totalCount'] = $totalCount;
				$attributes['pageCount'] = $pageCount;

				/* @var $page \Change\Presentation\Interfaces\Page */
				$page = $event->getParam('page');
				$section = $page->getSection();

				$webStoreId = $parameters->getParameter('webStoreId');
				$contextualUrls = $parameters->getParameter('contextualUrls');

				/* @var $product \Rbs\Catalog\Documents\Product */
				foreach ($query->getDocuments(($pageNumber-1)*$itemsPerPage, $itemsPerPage) as $product)
				{
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
			return 'product-list.twig';
		}
		return null;
	}
}
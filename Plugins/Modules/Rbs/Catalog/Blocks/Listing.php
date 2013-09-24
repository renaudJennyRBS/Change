<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Listing
 */
class Listing extends Block
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
		$parameters->addParameterMeta('listingId');
		$parameters->addParameterMeta('conditionId');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('listingId') !== null)
		{
			$documentManager = $event->getDocumentServices()->getDocumentManager();
			$listing = $documentManager->getDocumentInstance($parameters->getParameter('listingId'));
			if (!($listing instanceof \Rbs\Catalog\Documents\Listing) || !$listing->activated())
			{
				$parameters->setParameterValue('listingId', null);
			}
		}

		if ($parameters->getParameter('displayPrices') === null)
		{
			// TODO use webstore from commerce sevices
			//$webStore = $listing->getWebStore();
			if ($webStore)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}
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
		$listingId = $parameters->getParameter('listingId');
		if ($listingId)
		{
			/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
			$commerceServices = $event->getParam('commerceServices');
			$documentManager = $event->getDocumentServices()->getDocumentManager();

			$attributes['hasWebStore'] = false; // TODO use webstore from commerce sevices

			/* @var $listing \Rbs\Catalog\Documents\Listing */
			$listing = $documentManager->getDocumentInstance($listingId);
			$attributes['listing'] = $listing;

			$conditionId = $parameters->getParameter('conditionId');
			$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Catalog_Product');
			$query->andPredicates($query->published());
			$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductCategorization', 'product');
			$subQuery->andPredicates(
				$subQuery->eq('listing', $listingId),
				$subQuery->eq('condition', $conditionId ? $conditionId : 0),
				$subQuery->activated()
			);
			$subQuery->addOrder('position', true);
			$query->addOrder($listing->getProductSortOrder(), $listing->getProductSortDirection());

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

				// TODO use webstore from commerce sevices
				//$webStore = $listing->getWebStore();
				$webStoreId = $webStore ? $webStore->getId() : 0;
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

					$rows[] = (new \Rbs\Catalog\Std\ProductItem($row))->setDocumentManager($documentManager);
				}
			}
			$attributes['rows'] = $rows;

			$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');
			return 'listing.twig';
		}
		return null;
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
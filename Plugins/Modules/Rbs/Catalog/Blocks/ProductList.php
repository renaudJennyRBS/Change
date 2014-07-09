<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var array
	 */
	protected $validSortBy = ['title.asc', 'level.asc', 'price.asc', 'price.desc', 'price.desc', 'dateAdded.desc'];

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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('useCurrentSectionProductList');
		$parameters->addParameterMeta('conditionId');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('zone');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('showOrdering', true);
		$parameters->addParameterMeta('sortBy', null);

		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');
		$parameters->addParameterMeta('showUnavailable', true);

		$parameters->addParameterMeta('redirectUrl');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->setParameterValueForDetailBlock($parameters, $event);

		if ($parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) == null
			&& $parameters->getParameter('useCurrentSectionProductList') === true)
		{
			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$section = $page->getSection();

			$catalogManager = $commerceServices->getCatalogManager();
			$defaultProductList = $catalogManager->getDefaultProductListBySection($section);
			if ($this->isValidDocument($defaultProductList))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $defaultProductList->getId());
			}
		}

		if ($parameters->getParameter('showOrdering'))
		{
			$sortBy = $request->getQuery('sortBy-' . $event->getBlockLayout()->getId());
			if ($sortBy && in_array($sortBy, $this->validSortBy))
			{
				$parameters->setParameterValue('sortBy', $sortBy);
			}
		}

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$uri = $urlManager->getByFunction('Rbs_Commerce_Cart');
			if ($uri)
			{
				$parameters->setParameterValue('redirectUrl', $uri->normalize()->toString());
			}
		}

		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPrices') === null)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}

			$billingArea = $commerceServices->getContext()->getBillingArea();
			if ($billingArea)
			{
				$parameters->setParameterValue('billingAreaId', $billingArea->getId());
			}

			$zone = $commerceServices->getContext()->getZone();
			if ($zone)
			{
				$parameters->setParameterValue('zone', $zone);
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('billingAreaId', 0);
			$parameters->setParameterValue('zone', null);
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\ProductList && $document->activated())
		{
			return true;
		}
		return false;
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
		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productListId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $productList \Rbs\Catalog\Documents\ProductList */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) ||
				!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
			{
				return null;
			}

			$attributes['productList'] = $productList;

			$conditionId = $parameters->getParameter('conditionId');
			$query = $documentManager->getNewQuery('Rbs_Catalog_Product', $documentManager->getLCID());
			$predicates = [$query->published()];

			if (!$parameters->getParameter('showUnavailable'))
			{
				$predicates[] = $commerceServices->getStockManager()->getProductAvailabilityRestriction($query->getColumn('id'));
			}
			$query->andPredicates($predicates);

			$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
			$predicates = [
				$subQuery->eq('productList', $productListId),
				$subQuery->eq('condition', $conditionId ? $conditionId : 0),
				$subQuery->activated()
			];
			$subQuery->andPredicates($predicates);


			$billingAreaId = $parameters->getParameter('billingAreaId');
			$webStoreId = $parameters->getParameter('webStoreId');

			$defaultSortBy = $productList->getProductSortOrder() . '.' . $productList->getProductSortDirection();

			$queryBuilder = $this->addSort($parameters->getParameter('sortBy'), $defaultSortBy, $query, $subQuery, $webStoreId, $billingAreaId);
			$rows = array();
			$selectQuery = $queryBuilder->query();

			$totalCount = $this->getCountDocuments($query, $selectQuery);
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
				$attributes['section'] = $page->getSection();
				$contextualUrls = $parameters->getParameter('contextualUrls');
				$query->setQueryParameters($selectQuery);
				$selectQuery->setStartIndex(($pageNumber-1)*$itemsPerPage)->setMaxResults($itemsPerPage);
				$productIds = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id'));

				/* @var $product \Rbs\Catalog\Documents\Product */
				foreach ($productIds as $productId)
				{
					$product = $documentManager->getDocumentInstance($productId);
					if (!($product instanceof \Rbs\Catalog\Documents\Product))
					{
						continue;
					}
					if ($contextualUrls)
					{
						$url = $event->getUrlManager()->getByDocument($product, $section)->normalize()->toString();
					}
					else
					{
						$url = $event->getUrlManager()->getCanonicalByDocument($product)->normalize()->toString();
					}

					$row = array('id' => $product->getId(), 'url' => $url);

					$options = [ 'urlManager' => $event->getUrlManager() ];
					$productPresentation = $commerceServices->getCatalogManager()->getProductPresentation($product, $options);
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

	/**
	 * @param \Change\Documents\Query\Query $query
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return integer
	 */
	public function getCountDocuments($query, $selectQuery)
	{
		$qb = $query->getDbProvider()->getNewQueryBuilder();
		$fragmentBuilder = $qb->getFragmentBuilder();
		$qb->select($fragmentBuilder->alias($fragmentBuilder->func('count', '*'), 'rowCount'));
		$qb->from($fragmentBuilder->alias($fragmentBuilder->subQuery($selectQuery), '_tmpCount'));
		$selectCount = $qb->query();
		$query->setQueryParameters($selectCount);
		$count = $selectCount->getFirstResult($selectCount->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount'));
		return $count;
	}


	/**
	 * @param string|null $sortBy
	 * @param string $defaultSortBy
	 * @param \Change\Documents\Query\Query $query
	 * @param \Change\Documents\Query\ChildBuilder $subQuery
	 * @param integer $webStoreId
	 * @param integer $billingAreaId
	 * @return \Change\Db\Query\Builder
	 */
	protected function addSort($sortBy, $defaultSortBy, $query, $subQuery, $webStoreId, $billingAreaId)
	{
		$dbq = null;
		if ($sortBy == null)
		{
			$subQuery->addOrder('position', true);
			list($sortName, $sortDir) = explode('.', $defaultSortBy);
		}
		else
		{
			list($sortName, $sortDir) = explode('.', $sortBy);
		}

		if ($webStoreId && $billingAreaId && in_array($sortName, ['dateAdded', 'price', 'level']))
		{
			$dbq = $query->dbQueryBuilder();
			$fb = $dbq->getFragmentBuilder();
			$tableIdentifier = $fb->identifier('tb_sort');
			$join = $fb->logicAnd(
				$fb->eq($subQuery->getColumn('id'), $fb->column('listitem_id', $tableIdentifier)),
				$fb->eq($fb->column('store_id', $tableIdentifier), $fb->number($webStoreId)),
				$fb->eq($fb->column('billing_area_id', $tableIdentifier), $fb->number($billingAreaId))
			);
			$dbq->innerJoin($fb->alias($fb->table('rbs_catalog_dat_productlistitem'), $tableIdentifier), $join);

			if ($sortName == 'level')
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_level', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_level', $tableIdentifier));
				}
			}
			elseif ($sortName == 'price')
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_price', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_price', $tableIdentifier));
				}
			}
			else
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_date', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_date', $tableIdentifier));
				}
			}
		}
		elseif ($sortName == 'dateAdded')
		{
			$subQuery->addOrder('creationDate', $sortDir != 'desc');
		}
		else
		{
			$query->addOrder('title', $sortDir != 'desc');
		}

		if ($dbq === null)
		{
			$dbq = $query->dbQueryBuilder();
		}
		$dbq->addColumn($dbq->getFragmentBuilder()->alias($query->getColumn('id'), 'id'));
		return $dbq;
	}
}
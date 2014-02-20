<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Rest\Actions\DocumentQuery;
use Change\Http\Rest\Result\ArrayResult;
use Zend\Stdlib\Parameters;

/**
 * @name \Rbs\Catalog\Http\Rest\ProductListItemResult
 */
class ProductListItemResult
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function moveup(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->moveProductListItemUp($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function movedown(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->moveProductListItemDown($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlighttop(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductListItemTop($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlightbottom(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductListItemBottom($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlight(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductListItem($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function downplay(\Change\Http\Event $event)
	{
		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->downplayProductListItem($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductListItem');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function productListItemCollection(\Change\Http\Event $event)
	{
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$queryData = null;
		if ($document instanceof \Rbs\Catalog\Documents\ProductList)
		{
			$queryData = $this->buildQueryDataForProductList($document);
		}
		else if ($document instanceof \Rbs\Catalog\Documents\Product)
		{
			$queryData = $this->buildQueryDataForProduct($document);
		}
		if ($queryData)
		{
			$data = array_merge($event->getRequest()->getQuery()->toArray(), $queryData);
			$fakePostParameters = new Parameters($data);
			$event->getRequest()->setPost($fakePostParameters);
			$action = new DocumentQuery();
			$action->execute($event);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductList $document
	 * @return array
	 */
	protected function buildQueryDataForProductList($document)
	{
		return array (
			'model' => 'Rbs_Catalog_ProductListItem',
			'join' =>
			array (
				array (
					'model' => 'Rbs_Catalog_Product',
					'name' => 'j0',
					'property' => 'id',
					'parentProperty' => 'product',
				),
			),
			'where' =>
			array (
				'and' =>
				array (
					array (
						'op' => 'eq',
						'lexp' =>
						array (
							'property' => 'productList',
						),
						'rexp' =>
						array (
							'value' => $document->getId(),
						),
					),
				),
			),
			'order' =>
			array (
				array (
					'property' => 'position',
					'order' => 'asc',
				),
				array (
					'property' => $document->getProductSortOrder(),
					'join' => 'j0',
					'order' => $document->getProductSortDirection(),
				),
			),
		);
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $document
	 * @return array
	 */
	protected function buildQueryDataForProduct($document)
	{
		return array (
			'model' => 'Rbs_Catalog_ProductListItem',

			'where' =>
			array (
				'and' =>
				array (
					array (
						'op' => 'eq',
						'lexp' =>
						array (
							'property' => 'product',
						),
						'rexp' =>
						array (
							'value' => $document->getId(),
						),
					),
				),
			),
			'order' =>
			array (
				array (
					'property' => 'position',
					'order' => 'asc',
				),
			),
		);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function delete(\Change\Http\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();

		/* @var $cs \Rbs\Commerce\CommerceServices */
		$cs = $event->getServices('commerceServices');
		$cm = $cs->getCatalogManager();
		$result = array();
		try
		{
			$tm->begin();
			foreach ($event->getRequest()->getPost('documentIds') as $id)
			{
				$cat = $dm->getDocumentInstance($id);
				if ($cat instanceof \Rbs\Catalog\Documents\ProductListItem)
				{
					$cm->deleteProductListItem($cat);
					$result[] = $cat->getId();
				}
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$eventResult = new ArrayResult();
		$eventResult->setArray($result);
		$event->setResult($eventResult);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function addproducts(\Change\Http\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();

		/** @var $cs \Rbs\Commerce\CommerceServices */
		$cs = $event->getServices('commerceServices');

		/* @var $cm \Rbs\Catalog\CatalogManager */
		$cm = $cs->getCatalogManager();
		$productList = $dm->getDocumentInstance($event->getRequest()->getPost('productListId'));
		/* @var $condition \Rbs\Catalog\Documents\Condition|null */
		$condition = $dm->getDocumentInstance($event->getRequest()->getPost('conditionId'));
		$result = array();
		if ($productList instanceof \Rbs\Catalog\Documents\ProductList)
		{
			try
			{
				$tm->begin();
				foreach ($event->getRequest()->getPost('documentIds') as $id)
				{
					$product = $dm->getDocumentInstance($id);
					if ($product instanceof \Rbs\Catalog\Documents\Product)
					{
						$cat = $cm->getProductListItem($product, $productList, $condition);
						if (!$cat)
						{
							/* @var $cat \Rbs\Catalog\Documents\ProductListItem */
							$cat = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductListItem');
							$cat->setProduct($product);
							$cat->setProductList($productList);
							$cat->setCondition($condition);
							$cat->save();
							$result[] = $cat->getId();
						}
					}
				}
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
		$eventResult = new ArrayResult();
		$eventResult->setArray($result);
		$event->setResult($eventResult);
	}
}
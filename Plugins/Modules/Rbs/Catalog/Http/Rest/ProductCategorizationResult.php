<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Documents\Events\Event;
use Change\Http\Rest\Actions\DocumentQuery;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Stdlib\Parameters;

/**
 * @name \Rbs\Catalog\Http\Rest\ProductCategorizationResult
 */
class ProductCategorizationResult
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function moveup(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->moveProductCategorizationUp($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function movedown(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->moveProductCategorizationDown($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlighttop(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductCategorizationTop($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlightbottom(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductCategorizationBottom($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function highlight(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->highlightProductCategorization($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function downplay(\Change\Http\Event $event)
	{
		$cs = $event->getParam('commerceServices');
		if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cm = $cs->getCatalogManager();
			$cm->downplayProductCategorization($event->getParam('documentId'));
		}
		$event->setParam('modelName', 'Rbs_Catalog_ProductCategorization');
		$docAction = new \Change\Http\Rest\Actions\GetDocument();
		$docAction->execute($event);
	}



	/**
	 * @param \Change\Http\Event $event
	 */
	public function productCategorizationCollection(\Change\Http\Event $event)
	{
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$queryData = null;
		if ($document instanceof \Rbs\Catalog\Documents\Category)
		{
			$queryData = $this->buildQueryDataForCategory($document);
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
	 * @param \Rbs\Catalog\Documents\Category $document
	 * @return array
	 */
	protected function buildQueryDataForCategory($document)
	{
		return array (
			'model' => 'Rbs_Catalog_ProductCategorization',
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
							'property' => 'category',
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
			'model' => 'Rbs_Catalog_ProductCategorization',

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
		$dm = $event->getDocumentServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$cs = $event->getParam('commerceServices');
		/* @var $cm \Rbs\Catalog\Services\CatalogManager */
		$cm = $cs->getCatalogManager();
		$result = array();
		try
		{
			$tm->begin();
			foreach ($event->getRequest()->getPost('documentIds') as $id)
			{
				$cat = $dm->getDocumentInstance($id);
				if ($cat instanceof \Rbs\Catalog\Documents\ProductCategorization)
				{
					$cm->deleteProductCategorization($cat);
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
		$dm = $event->getDocumentServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$cs = $event->getParam('commerceServices');
		/* @var $cm \Rbs\Catalog\Services\CatalogManager */
		$cm = $cs->getCatalogManager();
		$category = $dm->getDocumentInstance($event->getRequest()->getPost('categoryId'));
		$condition = $dm->getDocumentInstance($event->getRequest()->getPost('conditionId'));
		$result = array();
		if ($category instanceof  \Rbs\Catalog\Documents\Category)
		{
			try
			{
				$tm->begin();
				foreach ($event->getRequest()->getPost('documentIds') as $id)
				{
					$product = $dm->getDocumentInstance($id);
					if ($product instanceof \Rbs\Catalog\Documents\AbstractProduct)
					{
						$cat = $cm->getProductCategorization($product, $category, $condition);
						if (!$cat)
						{
							/* @var $cat \Rbs\Catalog\Documents\ProductCategorization */
							$cat = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductCategorization');
							$cat->setProduct($product);
							$cat->setCategory($category);
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
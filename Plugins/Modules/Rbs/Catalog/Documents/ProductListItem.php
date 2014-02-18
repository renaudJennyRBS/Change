<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductListItem
 */
class ProductListItem extends \Compilation\Rbs\Catalog\Documents\ProductListItem
{
	/**
	 * @return boolean
	 */
	public function isHighlighted()
	{
		return $this->getPosition() < 0;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			/* @var $selfLink DocumentLink */
			$selfLink = $documentResult->getRelLink('self')[0];
			$pathInfo = $selfLink->getPathInfo();
			if ($this->isHighlighted())
			{
				$documentResult->addAction(new Link($um, $pathInfo . '/downplay', 'downplay'));
			}
			else
			{
				$documentResult->addAction(new Link($um, $pathInfo . '/highlight', 'highlight'));
			}
			$documentResult->addAction(new Link($um, $pathInfo . '/moveup', 'moveup'));
			$documentResult->addAction(new Link($um, $pathInfo . '/movedown', 'movedown'));
			$documentResult->addAction(new Link($um, $pathInfo . '/highlighttop', 'highlighttop'));
			$documentResult->addAction(new Link($um, $pathInfo . '/highlightbottom', 'highlightbottom'));
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			//$extraColumn = $event->getParam('extraColumn');
			$documentLink = $restResult;
			$urlManager = $documentLink->getUrlManager();
			$product = $this->getProduct();
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$documentLink->setProperty('product', new DocumentLink($urlManager, $product, DocumentLink::MODE_PROPERTY));
			}
			$productList = $this->getProductList();
			if ($productList instanceof \Rbs\Catalog\Documents\ProductList)
			{
				$documentLink->setProperty('productList',
					new DocumentLink($urlManager, $productList, DocumentLink::MODE_PROPERTY));
			}

			$documentLink->setProperty('isHighlighted', $this->isHighlighted());
			$documentLink->setProperty('position', $this->getPosition());

			$pathInfo = $documentLink->getPathInfo();

			if ($this->isHighlighted())
			{
				$actions[] = (new Link($urlManager, $pathInfo . '/downplay', 'downplay'))->toArray();
			}
			else
			{
				$actions[] = (new Link($urlManager, $pathInfo . '/highlight', 'highlight'))->toArray();
			}
			$actions[] = (new Link($urlManager, $pathInfo . '/moveup', 'moveup'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/movedown', 'movedown'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/highlighttop', 'highlighttop'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/highlightbottom', 'highlightbottom'))->toArray();
			$documentLink->setProperty('actions', $actions);
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_DELETED, array($this, 'onDeleted'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onCreated(\Change\Documents\Events\Event $event)
	{
		// Section product list synchronization.
		$list = $this->getProductList();
		$product = $this->getProduct();
		if ($list instanceof \Rbs\Catalog\Documents\SectionProductList && $product instanceof \Rbs\Catalog\Documents\Product)
		{
			$section = $list->getSynchronizedSection();
			if ($section && !in_array($section->getId(), $product->getPublicationSectionsIds()))
			{
				$product->getPublicationSections()->add($section);
				$product->save();
			}
		}
		elseif ($list instanceof \Rbs\Catalog\Documents\CrossSellingProductList
			&& $product instanceof \Rbs\Catalog\Documents\Product
		)
		{
			//CrossSellingList Symmetry
			if ($list->getSymmetrical())
			{
				$jm = $event->getApplicationServices()->getJobManager();
				$jm->createNewJob('Rbs_Catalog_UpdateSymmetricalProductListItem',
					array('listId' => $list->getId(), 'productId' => $product->getId(), 'action' => 'add'));
			}
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDeleted(\Change\Documents\Events\Event $event)
	{
		// Section product list synchronization.
		$product = $this->getProduct();
		$list = $this->getProductList();
		if ($list instanceof \Rbs\Catalog\Documents\SectionProductList && $product instanceof \Rbs\Catalog\Documents\Product)
		{
			$section = $list->getSynchronizedSection();
			if ($section && in_array($section->getId(), $product->getPublicationSectionsIds()))
			{
				$product->getPublicationSections()->remove($section);
				$product->save();
			}
		}
		elseif ($list instanceof \Rbs\Catalog\Documents\CrossSellingProductList
			&& $product instanceof \Rbs\Catalog\Documents\Product
		)
		{
			//CrossSellingList Symmetry
			if ($list->getSymmetrical())
			{
				$jm = $event->getApplicationServices()->getJobManager();
				$jm->createNewJob('Rbs_Catalog_UpdateSymmetricalProductListItem',
					array('listId' => $list->getId(), 'productId' => $product->getId(), 'action' => 'remove'));
			}
		}
	}
}

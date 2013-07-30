<?php
namespace Rbs\Catalog\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductCategorization
 */
class ProductCategorization extends \Compilation\Rbs\Catalog\Documents\ProductCategorization
{
	/**
	 * @return bool
	 */
	public function isHighlighted()
	{
		return $this->getPosition() < 0;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			/* @var $document \Rbs\Catalog\Documents\ProductCategorization */
			$document = $event->getDocument();
			if ($result instanceof DocumentResult)
			{

				$document->onProductCategorizationResult($event);
			}
			else if ($result instanceof DocumentLink)
			{
				$document->onProductCategorizationLink($event);
			}
		}, 5);
	}

	/**
	 * @param Event $event
	 */
	public function onProductCategorizationLink($event)
	{
		/* @var $document \Rbs\Catalog\Documents\ProductCategorization */
		$document = $event->getDocument();
		/* @var $result \Change\Http\Rest\Result\DocumentLink */
		$result = $event->getParam('restResult');
		$product = $document->getProduct();
		if ($product instanceof \Rbs\Catalog\Documents\AbstractProduct)
		{
			$firstVisual = $product->getFirstVisual();
			if ($firstVisual instanceof \Rbs\Media\Documents\Image)
			{
				$result->setProperty('productVisualId', $firstVisual->getId());
			}
			$result->setProperty('productLabel', $product->getLabel());
		}
		$category = $document->getCategory();
		if ($category instanceof \Rbs\Catalog\Documents\Category)
		{
			$result->setProperty('categoryLabel', $category->getLabel());
		}
		$result->setProperty('isHighlighted', $document->isHighlighted());
		$result->setProperty('position', $document->getPosition());
		$actions = array();
		$docLink = new DocumentLink($result->getUrlManager(), $document);

		if ($document->isHighlighted())
		{
			$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/downplay', 'downplay'))->toArray();
		}
		else
		{
			$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/highlight', 'highlight'))->toArray();
		}
		$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/moveup', 'moveup'))->toArray();
		$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/movedown', 'movedown'))->toArray();
		$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/highlighttop', 'highlighttop'))->toArray();
		$actions[] = (new Link($result->getUrlManager(), $docLink->getPathInfo() . '/highlightbottom', 'highlightbottom'))->toArray();
		$result->setProperty('actions', $actions);
	}

	/**
	 * @param Event $event
	 */
	public function onProductCategorizationResult(Event $event)
	{
		/* @var $document \Rbs\Catalog\Documents\ProductCategorization */
		$document = $event->getDocument();
		/* @var $result \Change\Http\Rest\Result\DocumentResult */
		$result = $event->getParam('restResult');
		$docLink = new DocumentLink($event->getParam('urlManager'), $document);
		if ($document->isHighlighted())
		{
			$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/downplay', 'downplay'));
		}
		else
		{
			$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/highlight', 'highlight'));
		}
		$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/moveup', 'moveup'));
		$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/movedown', 'movedown'));
		$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/highlighttop', 'highlighttop'));
		$result->addAction(new Link($event->getParam('urlManager'), $docLink->getPathInfo() . '/highlightbottom', 'highlightbottom'));
	}
}

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
	 * @param DocumentResult $documentResult
	 */
	public function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$um = $documentResult->getUrlManager();
		$selfLink = $documentResult->getRelLink('self')[0];
		$pathInfo = $selfLink->getPathInfo();
		if ($this->isHighlighted())
		{
			$documentResult->addAction(new Link($um, $pathInfo .  '/downplay', 'downplay'));
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

	/**
	 * @param DocumentLink $documentLink
	 * @param Array
	 */
	public function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);
		$urlManager = $documentLink->getUrlManager();
		$product = $this->getProduct();
		if ($product instanceof \Rbs\Catalog\Documents\AbstractProduct)
		{
			$documentLink->setProperty('product', new DocumentLink($urlManager, $product, DocumentLink::MODE_PROPERTY ));
		}
		$category = $this->getCategory();
		if ($category instanceof \Rbs\Catalog\Documents\Category)
		{
			$documentLink->setProperty('category', new DocumentLink($urlManager, $category, DocumentLink::MODE_PROPERTY ));
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

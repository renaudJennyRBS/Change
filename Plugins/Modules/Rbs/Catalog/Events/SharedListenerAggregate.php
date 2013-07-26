<?php
namespace Rbs\Catalog\Events;

use Zend\EventManager\SharedEventManagerInterface;

/**
 * @name \Rbs\Catalog\Events\SharedListenerAggregate
 */
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$callback = function (\Change\Documents\Events\Event $event)
		{
			$result = $event->getParam('restResult');
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$document = $event->getTarget();
				if ($document instanceof \Rbs\Catalog\Documents\Category)
				{
					$cr = new \Rbs\Catalog\Http\Rest\CatalogResult();
					$cr->onCategoryResult($event);
				}
				elseif ($document instanceof \Rbs\Catalog\Documents\AbstractProduct)
				{
					$cr = new \Rbs\Catalog\Http\Rest\CatalogResult();
					$cr->onProductResult($event);
				}
				elseif ($document instanceof \Rbs\Catalog\Documents\ProductCategorization)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
					$cr->onProductCategorizationResult($event);
				}
			}
			else if ($result instanceof \Change\Http\Rest\Result\DocumentLink)
			{
				$document = $event->getTarget();
				if ($document instanceof \Rbs\Catalog\Documents\ProductCategorization)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
					$cr->onProductCategorizationLink($event);
				}
			}
		};
		$events->attach(array('Rbs_Catalog_ProductCategorization', 'Rbs_Catalog_Category', 'Rbs_Catalog_AbstractProduct'), 'updateRestResult', $callback, 5);
		$events->attach('Http.Rest', 'http.action', array($this, 'registerActions'));
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function registerActions(\Change\Http\Event $event)
	{
		if (!$event->getAction())
		{
			$relativePath =  $event->getParam('pathInfo');
			if (preg_match('#^resources/Rbs/Catalog/ProductCategorization/([0-9]+)/(highlight|downplay|moveup|movedown|highlighttop|highlightbottom)$#', $relativePath, $matches))
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'Consumer', null, 'Rbs_Catalog_ProductCategorization');
				$event->setParam('documentId', intval($matches[1]));
				$methodName = $matches[2];
				$event->setAction(function($event) use ($methodName) {
					$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
					call_user_func(array($cr, $methodName), $event);
				});
			}
			else if (preg_match('#^resources/Rbs/Catalog/(Category|Product)/([0-9]+)/ProductCategorization/?$#', $relativePath, $matches))
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'Consumer', null, 'Rbs_Catalog_ProductCategorization');
				$event->setParam('documentId', intval($matches[2]));
				$event->setAction(function($event){
						$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
						$cr->productCategorizationCollection($event);
					});
			}
			else if ($relativePath === 'rbs/catalog/productcategorization/delete')
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'CategoryManager');
				$event->setAction(function($event){
					$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
					$cr->delete($event);
				});
			}
			else if ($relativePath === 'rbs/catalog/productcategorization/addproducts')
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'CategoryManager');
				$event->setAction(function($event){
					$cr = new \Rbs\Catalog\Http\Rest\ProductCategorizationResult();
					$cr->addproducts($event);
				});
			}
		}
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		// TODO: Implement detachShared() method.
	}
}
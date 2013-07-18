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
			}
		};
		$events->attach(array('Rbs_Catalog_Category', 'Rbs_Catalog_AbstractProduct'), 'updateRestResult', $callback, 5);
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
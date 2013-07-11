<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Rbs\Catalog\Http\Rest\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface, SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$ar = $event->getController()->getActionResolver();
			if ($ar instanceof \Change\Http\Rest\Resolver)
			{
				$ar->addResolverClasses('catalog', '\Rbs\Catalog\Http\Rest\CatalogResolver');
			}
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

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
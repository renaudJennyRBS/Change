<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fstauffer
 * Date: 11/07/13
 * Time: 23:03
 * To change this template use File | Settings | File Templates.
 */

namespace Rbs\Catalog\Events;


use Zend\EventManager\SharedEventManagerInterface;

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

		$this->registerCollections($events);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		// TODO: Implement detachShared() method.
	}

	/**
	 * @param SharedEventManagerInterface $events
	 */
	protected function registerCollections(SharedEventManagerInterface $events)
	{
		$callback = function (\Zend\EventManager\Event $event)
		{
			if ($event->getParam('code') === 'Rbs_Catalog_Collection_Shops')
			{
				$event->setParam('collection', new \Rbs\Catalog\Collection\ShopsCollection($event->getParam('documentServices')));
			}
		};
		$events->attach('CollectionManager', 'getCollection', $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes');
			$codes[] = 'Rbs_Catalog_Collection_Shops';
			$event->setParam('codes', $codes);
		};
		$events->attach('CollectionManager', 'getCodes', $callback, 1);
	}
}
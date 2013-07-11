<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fredericbonjour
 * Date: 11/07/13
 * Time: 12:52
 * To change this template use File | Settings | File Templates.
 */

namespace Rbs\Admin\Events;


use Zend\EventManager\EventManagerInterface;

class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface {

	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param EventManagerInterface $events
	 *
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		// TODO: Implement attach() method.
		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new GetAvailablePageFunctions();
			return $resolver->execute($event);
		};
		$events->attach(\Change\Collection\CollectionManager::EVENT_GET_COLLECTION, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param EventManagerInterface $events
	 *
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
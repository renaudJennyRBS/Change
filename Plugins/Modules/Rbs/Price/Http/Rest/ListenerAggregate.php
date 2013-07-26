<?php
namespace Rbs\Price\Http\rest;

use Rbs\Price\Http\Rest\Actions\TaxInfo;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Price\Http\rest\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function registerActions(\Change\Http\Event $event)
	{
		if (!$event->getAction() && $event->getParam('pathInfo') === 'rbs/price/taxInfo')
		{
			$event->setAction(function ($event)
			{
				(new TaxInfo())->execute($event);
			});
		}
	}

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach('http.action', array($this, 'registerActions'));
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
}
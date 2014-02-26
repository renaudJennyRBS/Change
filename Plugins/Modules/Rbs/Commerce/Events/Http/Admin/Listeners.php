<?php
namespace Rbs\Commerce\Events\Http\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Http\Admin\Listeners
 */
class Listeners implements ListenerAggregateInterface
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
		$events->attach(\Change\Http\Event::EVENT_ACTION, array($this, 'registerActions'));
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
	 * @param \Change\Http\Event $event
	 */
	public function registerActions(\Change\Http\Event $event)
	{
		$relativePath = $event->getParam('resourcePath');
		if ($relativePath === 'Rbs/Commerce/cartFiltersDefinition.twig')
		{
			$event->setAction(function ($event)
			{
				(new \Rbs\Commerce\Http\Admin\Actions\CartFiltersDefinition())->execute($event);
			});
		}
	}
}
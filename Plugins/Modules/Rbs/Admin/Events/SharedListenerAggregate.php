<?php
namespace Rbs\Admin\Events;

use Rbs\Website\Events\WebsiteResolver;
use Change\Documents\Events\Event as DocumentEvent;

/**
* @name \Rbs\Admin\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$events->attach('Http.Rest', 'http.action', array($this, 'registerActions'));
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function registerActions(\Change\Http\Event $event)
	{
		if ($event->getAction())
		{
			return;
		}

		$path = implode('/', $event->getParam('pathParts'));

		switch ($path)
		{
			case 'Rbs/PublishableModels' :
				$action = new \Rbs\Admin\Http\Rest\Actions\PublishableModels();
				break;
		}

		if ($action)
		{
			$event->setAction(function ($event) use ($action) {
				$action->execute($event);
			});
		}
	}


	/**
	 * Detach all previously attached listeners
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function detachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		//TODO
	}
}
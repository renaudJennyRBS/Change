<?php
namespace Rbs\Media\Http\Rest;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Media\Http\Rest\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
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
		if (!$event->getAction())
		{
			$relativePath =  $event->getParam('pathInfo');
			if (preg_match('#^resources/Rbs/Media/Image/([0-9]+)/resize$#', $relativePath, $matches))
			{
				//$event->getController()->getActionResolver()->setAuthorization($event, 'Consumer', null, 'Rbs_Media_Image');
				$event->setParam('documentId', intval($matches[1]));
				$event->setAction(function($event) {
					$action = new \Rbs\Media\Http\Rest\Actions\Resize();
					$action->resize($event);
				});
			}
		}
	}
}
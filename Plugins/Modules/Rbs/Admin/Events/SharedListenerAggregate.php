<?php
namespace Rbs\Admin\Events;
use Rbs\Admin\MarkdownParser;


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
		$events->attach('Presentation.RichText', 'GetParser', array($this, 'getRichTextParser'));
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

		if ($event->getParam('pathInfo') === 'Rbs/ModelsInfo')
		{
			$action = function ($event) {
				(new \Rbs\Admin\Http\Rest\Actions\ModelsInfo())->execute($event);
			};
			$event->setAction($action);
		}
	}

	/**
	 * @param \Change\Presentation\RichText\Event $event
	 */
	public function getRichTextParser(\Change\Presentation\RichText\Event $event)
	{
		if ($event->getProfile() === 'Admin')
		{
			if ($event->getEditor() === 'Markdown')
			{
				$event->setParser(new MarkdownParser($event->getDocumentServices()));
			}
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
<?php
namespace Rbs\Generic\Events\RichTextManager;

use Change\Presentation\RichText\Event;
use Change\Presentation\RichText\RichTextManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\RichTextManager\Listeners
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
		$callback = function (Event $event)
		{
			if ($event->getEditor() === 'Markdown')
			{
				if ($event->getProfile() === 'Admin')
				{
					$event->setParser(new \Rbs\Admin\MarkdownParser($event->getDocumentServices()));
				}
				elseif ($event->getProfile() === 'Website')
				{
					$event->setParser(new \Rbs\Website\RichText\MarkdownParser($event->getDocumentServices()));
				}
			}
		};
		$events->attach(RichTextManager::EVENT_GET_PARSER, $callback, 5);
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
<?php
namespace Rbs\Website\Events;

use Rbs\Website\Events\WebsiteResolver;
use Change\Documents\Events\Event as DocumentEvent;
use Rbs\Website\RichText\MarkdownParser;

/**
* @name \Rbs\Website\Events\SharedListenerAggregate
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
		$callback = function (\Change\Http\Web\Event $event)
		{
			$resolver = new WebsiteResolver();
			$resolver->resolve($event);
		};
		$events->attach('Http.Web', \Change\Http\Event::EVENT_REQUEST, $callback, 5);

		$callback = function (\Change\Documents\Events\Event $event)
		{
			$website = $event->getDocument();
			if ($website instanceof \Rbs\Website\Documents\Website)
			{
				$resolver = new WebsiteResolver();
				$resolver->changed($website);
			}
		};

		$eventNames = array(DocumentEvent::EVENT_CREATED, DocumentEvent::EVENT_UPDATED);
		$events->attach('Rbs_Website_Website', $eventNames, $callback, 5);

		$events->attach('Http.Rest', 'http.action', array($this, 'registerActions'));

		$callback = function (\Change\Documents\Events\Event $event)
		{
			$resolver = new PageResolver();
			$resolver->resolve($event);
		};
		$events->attach('Documents', DocumentEvent::EVENT_DISPLAY_PAGE, $callback, 5);

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

		switch ($event->getParam('pathInfo'))
		{
			case 'Rbs/Website/FunctionsList' :
				$action = new \Rbs\Website\Http\Rest\Actions\FunctionsList();
				break;
			case 'Rbs/Website/PagesForFunction' :
				$action = new \Rbs\Website\Http\Rest\Actions\PagesForFunction();
				break;
			default :
				$action = null;
		}

		if ($action)
		{
			$event->setAction(function ($event) use ($action) {
				$action->execute($event);
			});
		}
	}

	/**
	 * @param \Change\Presentation\RichText\Event $event
	 */
	public function getRichTextParser(\Change\Presentation\RichText\Event $event)
	{
		if ($event->getProfile() === 'Website')
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
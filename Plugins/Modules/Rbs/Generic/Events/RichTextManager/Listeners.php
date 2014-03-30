<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
					$event->setParser(new \Rbs\Admin\MarkdownParser($event->getApplicationServices()));
				}
				elseif ($event->getProfile() === 'Website')
				{
					$event->setParser(new \Rbs\Website\RichText\MarkdownParser($event->getApplicationServices()));
				}
			}
			else if ($event->getEditor() === 'Html')
			{
				$event->setParser(new \Rbs\Admin\WysiwygHtmlParser($event->getApplicationServices()));
			}
			else
			{
				$event->setParser(new \Rbs\Admin\PlainTextParser());
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
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\RichTextManager;

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
		$callback = function (\Change\Events\Event $event)
		{
			if (!$event->getParam('html'))
			{
				$parser = null;
				$richText = $event->getParam('richText');
				if ($richText->getEditor() === 'Markdown')
				{
					$profile = $event->getParam('profile');
					if ($profile === 'Admin')
					{
						$parser = new \Rbs\Admin\MarkdownParser($event->getApplicationServices());
					}
					elseif ($profile === 'Website' || $profile === 'Mail')
					{
						$parser = new \Rbs\Website\RichText\MarkdownParser($event->getApplicationServices());
					}
				}
				else if ($richText->getEditor() === 'Html')
				{
					$parser = new \Rbs\Admin\WysiwygHtmlParser($event->getApplicationServices());
				}
				else
				{
					$parser = new \Rbs\Admin\PlainTextParser();
				}

				if ($parser)
				{
					$event->setParam('html', $parser->parse($richText->getRawText(), $event->getParam('context')));
				}
			}
		};
		$events->attach(RichTextManager::EVENT_RENDER, $callback, 5);

		$callback = function (\Change\Events\Event $event)
		{
			$html = $event->getParam('html');
			if ($html && $event->getParam('profile') === 'Website')
			{
				$processor = new \Rbs\Website\RichText\PostProcessor();
				$event->setParam('html', $processor->process($html, $event->getParam('context')));
			}
		};
		$events->attach(RichTextManager::EVENT_RENDER, $callback, 1);
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
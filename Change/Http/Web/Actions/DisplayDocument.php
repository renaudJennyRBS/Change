<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Web\Event;
use Change\Http\Web\PathRule;
use Change\Presentation\Interfaces\Page;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\DisplayDocument
 */
class DisplayDocument
{
	/**
	 * Use Required Event Params: pathRule
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getPathRule();
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}

		$requestQuery = $event->getRequest()->getQuery();
		if ($pathRule->getQuery())
		{
			$requestQuery->fromArray(\Zend\Stdlib\ArrayUtils::merge($pathRule->getQueryParameters(), $requestQuery->toArray()));
		}
		$pathRule->setQueryParameters($requestQuery->toArray());

		$document = $event->getDocument();
		if ($document instanceof AbstractDocument)
		{
			$documentEvent = new DocumentEvent(DocumentEvent::EVENT_DISPLAY_PAGE, $document, $event->getParams());
			$document->getEventManager()->trigger($documentEvent);
			$page = $documentEvent->getParam('page');
			if ($page instanceof Page)
			{
				$event->getUrlManager()->setSection($page->getSection());
				$event->setParam('page', $page);
			}
		}
	}
}

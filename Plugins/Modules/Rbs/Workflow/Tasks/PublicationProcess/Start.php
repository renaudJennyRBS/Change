<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Events\Event;

/**
* @name \Rbs\Workflow\Tasks\PublicationProcess\Start
*/
class Start
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$wm = $event->getApplicationServices()->getWorkflowManager();
			$ctx = array(\Change\Workflow\Interfaces\WorkItem::DOCUMENT_ID_CONTEXT_KEY => $document->getId());
			$wm->getNewWorkflowInstance('publicationProcess', $ctx);
		}
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Documents\Events\Event;
use Change\Documents\Correction;
use Change\Workflow\Interfaces\WorkItem;

/**
* @name \Rbs\Workflow\Tasks\CorrectionPublicationProcess\Start
*/
class Start
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$correction = $event->getParam('correction');
		if ($correction instanceof Correction && $event->getDocument())
		{
				$document = $event->getDocument();
				$wm = $event->getApplicationServices()->getWorkflowManager();
				$ctx = array(WorkItem::DOCUMENT_ID_CONTEXT_KEY => $document->getId());
				$ctx[WorkItem::CORRECTION_ID_CONTEXT_KEY] = $correction->getId();
				$wm->getNewWorkflowInstance('correctionPublicationProcess', $ctx);
		}
	}
}
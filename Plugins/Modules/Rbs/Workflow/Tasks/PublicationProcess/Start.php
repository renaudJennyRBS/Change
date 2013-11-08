<?php
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
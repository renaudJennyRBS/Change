<?php
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Documents\Events\Event;
use Change\Documents\Correction;
use Change\Workflow\Interfaces\WorkItem;
use Change\Workflow\WorkflowManager;

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
				$ctx['__CORRECTION_ID'] = $correction->getId();
				$wm->getNewWorkflowInstance('correctionPublicationProcess', $ctx);
		}
	}
}
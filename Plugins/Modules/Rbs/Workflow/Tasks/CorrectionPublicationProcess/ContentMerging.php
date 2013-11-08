<?php
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Documents\Interfaces\Correction;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;

/**
 * @name \Rbs\Workflow\Tasks\CorrectionPublicationProcess\ContentMerging
 */
class ContentMerging
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		/* @var $workItem WorkItem */
		$workItem = $event->getParam('workItem');
		$ctx = $workItem->getContext();
		$document = $workItem->getWorkflowInstance()->getDocument();
		if ($document instanceof Correction)
		{
			$correction = $document->getCurrentCorrection();
			if ($correction && $correction->getId() == $ctx['__CORRECTION_ID'])
			{
				$applicationServices = $event->getApplicationServices();
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();
					if (!$document->mergeCurrentCorrection())
					{
						throw new \RuntimeException('Unable to merge correction :' . $correction, 999999);
					}
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}
}
<?php
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Documents\Interfaces\Correction;
use Change\Documents\Correction as CorrectionInstance;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;

/**
* @name \Rbs\Workflow\Tasks\CorrectionPublicationProcess\PublicationValidation
*/
class PublicationValidation
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
		if (isset($ctx[WorkItem::PRECONDITION_CONTEXT_KEY]))
		{
			unset($ctx[WorkItem::PRECONDITION_CONTEXT_KEY]);
		}

		$document = $workItem->getWorkflowInstance()->getDocument();
		if ($document instanceof Correction)
		{
			$correction = $document->getCurrentCorrection();
			if ($correction && $correction->getId() == $ctx[WorkItem::CORRECTION_ID_CONTEXT_KEY])
			{
				$applicationServices = $event->getApplicationServices();
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();

					if (isset($ctx['publicationDate']))
					{
						$publicationDate = $ctx['publicationDate'];
						if (is_string($publicationDate))
						{
							$publicationDate = new \DateTime($publicationDate);
						}
						if ($publicationDate instanceof \DateTime)
						{
							$correction->setPublicationDate($publicationDate);
						}
						else
						{
							$correction->setPublicationDate(null);
						}
						unset($ctx['publicationDate']);
					}

					$deadLine = $correction->getPublicationDate();
					if ($deadLine instanceof \DateTime)
					{
						$ctx[WorkItem::PRECONDITION_CONTEXT_KEY] = 'DELAYED';
						$newPublicationStatus = CorrectionInstance::STATUS_VALID;
						$ctx['contentMergingDeadLine'] = $deadLine->format(\DateTime::ISO8601);
					}
					else
					{
						$newPublicationStatus = CorrectionInstance::STATUS_PUBLISHABLE;
						if (isset($ctx['contentMergingDeadLine']))
						{
							unset($ctx['contentMergingDeadLine']);
						}
					}
					$correction->setStatus($newPublicationStatus);
					$correction->updateStatus();

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
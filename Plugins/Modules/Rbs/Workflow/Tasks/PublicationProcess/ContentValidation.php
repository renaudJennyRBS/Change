<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Zend\EventManager\Event;

/**
 * @name \Rbs\Workflow\Tasks\PublicationProcess\ContentValidation
 */
class ContentValidation
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
		if ($document instanceof Publishable)
		{
			$publicationStatus = $document->getDocumentModel()->getPropertyValue($document, 'publicationStatus');
			if ($publicationStatus === Publishable::STATUS_VALIDATION)
			{
				$documentServices = $document->getDocumentServices();
				$newPublicationStatus = Publishable::STATUS_VALIDCONTENT;

				if (isset($ctx['reason']))
				{
					$reason = trim($ctx['reason']);
					if (!empty($reason))
					{
						$ctx[WorkItem::PRECONDITION_CONTEXT_KEY] = 'NO';
						$newPublicationStatus = Publishable::STATUS_DRAFT;
					}
					unset($ctx['reason']);
				}

				$transactionManager = $documentServices->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();

					$document->updatePublicationStatus($newPublicationStatus);

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
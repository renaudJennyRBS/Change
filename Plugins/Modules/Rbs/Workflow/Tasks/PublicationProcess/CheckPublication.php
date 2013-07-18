<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Zend\EventManager\Event;
/**
* @name \Rbs\Workflow\Tasks\PublicationProcess\CheckPublication
*/
class CheckPublication
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
			$publicationStatus = $document->getPublicationStatus();
			if ($publicationStatus === Publishable::STATUS_VALID ||
				$publicationStatus === Publishable::STATUS_UNPUBLISHABLE ||
				$publicationStatus === Publishable::STATUS_PUBLISHABLE)
			{

				$reason = $document->isPublishable();
				if (is_string($reason))
				{
					$ctx[WorkItem::PRECONDITION_CONTEXT_KEY] = 'NO';
					$newPublicationStatus = Publishable::STATUS_UNPUBLISHABLE;
				}
				else
				{
					$reason = null;
					$newPublicationStatus = Publishable::STATUS_PUBLISHABLE;
					if ($document->getEndPublication())
					{
						$ctx['fileDeadLine'] = $document->getEndPublication()->format(\DateTime::ISO8601);
					}
					elseif (isset($ctx['fileDeadLine']))
					{
						unset($ctx['fileDeadLine']);
					}
				}

				$documentServices = $document->getDocumentServices();
				$transactionManager = $documentServices->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();

					$this->setUnpublishableReason($document, $reason);

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

	/**
	 * @param \Change\Documents\Traits\DbStorage $document
	 * @param string|null $reason
	 */
	protected function setUnpublishableReason($document, $reason)
	{
		if ($document instanceof Localizable)
		{
			$metaKey = 'Unpublishable_Reason_' . $document->getLCID();
		}
		else
		{
			$metaKey = 'Unpublishable_Reason';
		}
		$document->setMeta($metaKey, $reason);
		if ($document->hasModifiedMetas())
		{
			$document->saveMetas();
		}
	}
}
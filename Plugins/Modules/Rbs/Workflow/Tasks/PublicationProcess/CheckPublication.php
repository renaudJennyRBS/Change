<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;
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
			$publicationStatus = $document->getDocumentModel()->getPropertyValue($document, 'publicationStatus');
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
					$endPublication = $document->getDocumentModel()->getPropertyValue($document, 'endPublication');
					if ($endPublication)
					{
						$ctx['fileDeadLine'] = $endPublication->format(\DateTime::ISO8601);
					}
					elseif (isset($ctx['fileDeadLine']))
					{
						unset($ctx['fileDeadLine']);
					}
				}

				$applicationServices = $event->getApplicationServices();
				$transactionManager = $applicationServices->getTransactionManager();
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
			$metaKey = 'Unpublishable_Reason_' . $document->getCurrentLCID();
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
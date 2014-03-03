<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;

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
				$applicationServices = $event->getApplicationServices();
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

				$transactionManager = $applicationServices->getTransactionManager();
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
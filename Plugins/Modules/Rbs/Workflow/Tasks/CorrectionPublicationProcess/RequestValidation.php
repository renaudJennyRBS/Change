<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Documents\Interfaces\Correction;
use Change\Documents\Correction as CorrectionInstance;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;

/**
* @name \Rbs\Workflow\Tasks\CorrectionPublicationProcess\RequestValidation
*/
class RequestValidation
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
			if ($correction && $correction->getId() == $ctx[WorkItem::CORRECTION_ID_CONTEXT_KEY])
			{
				$applicationServices = $event->getApplicationServices();
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$correction->setStatus(CorrectionInstance::STATUS_VALIDATION);
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
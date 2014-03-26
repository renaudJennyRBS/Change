<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Events;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Publishable;

/**
* @name \Change\Documents\Events\PublishListener
*/
class PublishListener
{
	/**
	 * @param DocumentEvent $event
	 */
	public function onUpdated($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$document = $event->getDocument();
			if ($document instanceof AbstractDocument)
			{
				$modifiedPropertyNames = $event->getParam('modifiedPropertyNames', []);
				if (count(array_intersect(['publicationStatus', 'startPublication', 'endPublication'], $modifiedPropertyNames)))
				{
					$model = $document->getDocumentModel();
					$applicationServices = $event->getApplicationServices();
					$publicationStatus = $model->getPropertyValue($document, 'publicationStatus');
					if ($publicationStatus === Publishable::STATUS_PUBLISHABLE)
					{
						$now = new \DateTime();
						if (in_array('startPublication', $modifiedPropertyNames))
						{
							$startPublication = $model->getPropertyValue($document, 'startPublication');
							if ($startPublication instanceof \DateTime && $startPublication > $now)
							{
								$arguments = ['documentId' => $document->getId(), 'startPublication' => $startPublication->format('c')];
								$applicationServices->getJobManager()->createNewJob('scheduledPublication', $arguments, $startPublication);
							}
						}

						if (in_array('endPublication', $modifiedPropertyNames))
						{
							$endPublication = $model->getPropertyValue($document, 'endPublication');
							if ($endPublication instanceof \DateTime && $endPublication > $now)
							{
								$arguments = ['documentId' => $document->getId(), 'endPublication' => $endPublication->format('c')];
								$endPublication = $endPublication->add(new \DateInterval('PT1M'));
								$applicationServices->getJobManager()->createNewJob('scheduledPublication', $arguments, $endPublication);
							}
						}
					}
				}
				elseif (count(array_intersect(['active', 'startActivation', 'endActivation'], $modifiedPropertyNames)))
				{
					$now = new \DateTime();
					$model = $document->getDocumentModel();
					$applicationServices = $event->getApplicationServices();
					$active = $model->getPropertyValue($document, 'active');
					if ($active === true)
					{
						if (in_array('startActivation', $modifiedPropertyNames))
						{
							$startActivation = $model->getPropertyValue($document, 'startActivation');
							if ($startActivation instanceof \DateTime && $startActivation > $now)
							{
								$arguments = ['documentId' => $document->getId(), 'startActivation' => $startActivation->format('c')];
								$applicationServices->getJobManager()->createNewJob('scheduledActivation', $arguments, $startActivation);
							}
						}

						if (in_array('endActivation', $modifiedPropertyNames))
						{
							$endActivation = $model->getPropertyValue($document, 'endActivation');
							if ($endActivation instanceof \DateTime && $endActivation > $now)
							{
								$arguments = ['documentId' => $document->getId(), 'endActivation' => $endActivation->format('c')];
								$endActivation = $endActivation->add(new \DateInterval('PT1M'));
								$applicationServices->getJobManager()->createNewJob('scheduledActivation', $arguments, $endActivation);
							}
						}
					}
				}
			}
		}
	}
} 
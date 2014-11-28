<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Job;

use Rbs\Productreturn\Documents\ProductReturn;

/**
 * @name \Rbs\Productreturn\Job\Notification
 */
class Notification
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$notificationName = $job->getArgument('notificationName');
		switch ($notificationName)
		{
			case 'rbs_productreturn_status_accepted':
				$this->rbsProductreturnStatusMail($event, ProductReturn::PROCESSING_STATUS_RECEPTION, $notificationName);
				$event->success();
				break;

			case 'rbs_productreturn_status_received':
				$this->rbsProductreturnStatusMail($event, ProductReturn::PROCESSING_STATUS_PROCESSING, $notificationName);
				$event->success();
				break;

			case 'rbs_productreturn_status_finalized':
				$this->rbsProductreturnStatusMail($event, ProductReturn::PROCESSING_STATUS_FINALIZED, $notificationName);
				$event->success();
				break;

			case 'rbs_productreturn_status_canceled':
				$this->rbsProductreturnStatusMail($event, ProductReturn::PROCESSING_STATUS_CANCELED, $notificationName);
				$event->success();
				break;

			case 'rbs_productreturn_status_refused':
				$this->rbsProductreturnStatusMail($event, ProductReturn::PROCESSING_STATUS_REFUSED, $notificationName);
				$event->success();
				break;
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 * @param string $expectedStatus
	 * @param string $notificationName
	 */
	protected function rbsProductreturnStatusMail(\Change\Job\Event $event, $expectedStatus, $notificationName)
	{
		$job = $event->getJob();
		$targetId = $job->getArgument('targetId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var ProductReturn $return */
		$return = $documentManager->getDocumentInstance($targetId, 'Rbs_Productreturn_ProductReturn');
		if (!$return || $return->getProcessingStatus() != $expectedStatus)
		{
			return;
		}

		$order = $return->getOrderIdInstance();
		if (!$order)
		{
			return;
		}

		$website = $documentManager->getDocumentInstance($order->getContext()->get('websiteId'));
		$LCID = $order->getContext()->get('LCID');
		if ($website instanceof \Rbs\Website\Documents\Website && $LCID)
		{
			$documentManager->pushLCID($LCID);

			$owner = $order->getOwnerIdInstance();
			$userEmail = ($owner instanceof \Rbs\User\Documents\User) ? $owner->getEmail() : $order->getEmail();
			$fullName = $this->getFullName($owner, $event);

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$mailManager = $genericServices->getMailManager();
			try
			{
				$mailManager->send($notificationName, $website, $LCID, $userEmail,
					['website' => $website->getTitle(), 'fullName' => $fullName,
						'orderCode' => $order->getCode(), 'orderId' => $order->getId(),
						'productReturnCode' => $return->getCode(), 'productReturnId' => $return->getId(),
						'processingComment' => \Change\Stdlib\String::htmlEscape($return->getProcessingComment())]);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->info($e);
			}
			$documentManager->popLCID();
		}
	}

	/**
	 * @param \Rbs\User\Documents\User|null $owner
	 * @param \Change\Job\Event $event
	 * @return string
	 */
	protected function getFullName($owner, \Change\Job\Event $event)
	{
		if ($owner instanceof \Rbs\User\Documents\User)
		{
			$profileManager = $event->getApplicationServices()->getProfileManager();
			$profile = $profileManager->loadProfile(new \Rbs\User\Events\AuthenticatedUser($owner), 'Rbs_User');
			$fullName = $profile->getPropertyValue('fullName');
			if ($fullName)
			{
				return ' ' . $fullName;
			}
		}
		return '';
	}
} 
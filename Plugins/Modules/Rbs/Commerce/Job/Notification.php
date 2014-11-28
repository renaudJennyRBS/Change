<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Job;

/**
 * @name \Rbs\Commerce\Job\Notification
 */
class Notification
{
	/**
	 * @param \Change\Job\Event $event
	 */
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		switch ($job->getArgument('notificationName'))
		{
			case 'rbs_commerce_order_shipment_under_preparation':
				$this->rbsCommerceOrderShipmentUnderPreparation($event);
				$event->success();
				break;

			case 'rbs_commerce_order_shipment_sent':
				$this->rbsCommerceOrderShipmentSent($event);
				$event->success();
				break;

			case 'rbs_commerce_order_confirmation':
				$this->rbsCommerceOrderConfirmation($event);
				$event->success();
				break;

			case 'rbs_commerce_order_canceled':
				$this->rbsCommerceOrderCanceled($event);
				$event->success();
				break;
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderShipmentUnderPreparation(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Shipment $shipment */
		$shipment = $documentManager->getDocumentInstance($job->getArgument('targetId'), 'Rbs_Order_Shipment');
		if (!$shipment)
		{
			return;
		}

		$order = $shipment->getOrderIdInstance();
		if (!$order)
		{
			return;
		}

		$orderProcess = $this->getOrderProcess($order);
		if (!$orderProcess || !$orderProcess->getSendMailShipmentPreparation())
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
				$mailManager->send('rbs_commerce_order_shipment_under_preparation', $website, $LCID, $userEmail,
					['website' => $website->getTitle(), 'fullName' => $fullName, 'orderCode' => $order->getCode(),
						'orderId' => $order->getId(), 'shipmentCode' => $shipment->getCode(),
						'shipmentId' => $shipment->getId()]);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->info($e);
			}
			$documentManager->popLCID();
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderShipmentSent(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Shipment $shipment */
		$shipment = $documentManager->getDocumentInstance($job->getArgument('targetId'), 'Rbs_Order_Shipment');
		if (!$shipment || !$shipment->getPrepared() || $shipment->getShippingDate() == null)
		{
			return;
		}

		$order = $shipment->getOrderIdInstance();
		if (!$order)
		{
			return;
		}

		$orderProcess = $this->getOrderProcess($order);
		if (!$orderProcess || !$orderProcess->getSendMailShipmentFinalized())
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
				$mailManager->send('rbs_commerce_order_shipment_sent', $website, $LCID, $userEmail,
					['website' => $website->getTitle(), 'fullName' => $fullName, 'orderCode' => $order->getCode(),
						'orderId' => $order->getId(), 'shipmentCode' => $shipment->getCode(), 'shipmentId' => $shipment->getId()],
					$shipment->getShippingDate());
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->info($e);
			}
			$documentManager->popLCID();
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderConfirmation(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Order $order */
		$order = $documentManager->getDocumentInstance($job->getArgument('targetId'), 'Rbs_Order_Order');
		if (!$order)
		{
			return;
		}

		$orderProcess = $this->getOrderProcess($order);
		if (!$orderProcess || !$orderProcess->getSendMailOrderConfirm())
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
				$mailManager->send('rbs_commerce_order_confirmation', $website, $LCID, $userEmail,
					['website' => $website->getTitle(), 'fullName' => $fullName, 'total' => $this->getOrderAmount($order, $event),
						'orderCode' => $order->getCode(), 'orderId' => $order->getId()]);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->info($e);
			}
			$documentManager->popLCID();
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderCanceled(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Order $order */
		$order = $documentManager->getDocumentInstance($job->getArgument('targetId'), 'Rbs_Order_Order');
		if (!$order || $order->getProcessingStatus() != \Rbs\Order\Documents\Order::PROCESSING_STATUS_CANCELED)
		{
			return;
		}

		$orderProcess = $this->getOrderProcess($order);
		if (!$orderProcess || !$orderProcess->getSendMailOrderCanceled())
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
				$mailManager->send('rbs_commerce_order_canceled', $website, $LCID, $userEmail,
					['website' => $website->getTitle(), 'fullName' => $fullName, 'total' => $this->getOrderAmount($order, $event),
						'orderCode' => $order->getCode(), 'orderId' => $order->getId()]);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->info($e);
			}
			$documentManager->popLCID();
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Order $order
	 * @return \Rbs\Commerce\Documents\Process|null
	 */
	protected function getOrderProcess($order)
	{
		$webStore = $order->getWebStoreIdInstance();
		if ($webStore)
		{
			return $webStore->getOrderProcess();
		}
		return null;
	}

	/**
	 * @param \Rbs\Order\Documents\Order $order
	 * @param \Change\Job\Event $event
	 * @return \Rbs\Commerce\Documents\Process|null
	 */
	protected function getOrderAmount($order, \Change\Job\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$pm = $commerceServices->getPriceManager();
			$currency = $order->getCurrencyCode();
			return $pm->formatValue($order->getPaymentAmount(), $currency);
		}
		else
		{
			return $order->getPaymentAmount();
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
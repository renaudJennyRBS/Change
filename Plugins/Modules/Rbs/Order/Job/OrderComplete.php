<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Job;

use \Rbs\Order\Documents\Order;

/**
 * @name \Rbs\Order\Job\OrderComplete
 */
class OrderComplete
{
	/**
	 * @param \Change\Job\Event $event
	 */
	public function execute(\Change\Job\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$stockManager = $commerceServices->getStockManager();

			$job = $event->getJob();

			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($job->getArgument('orderId'));
			if ($order instanceof Order)
			{
				$processingStatus = $order->getProcessingStatus();
				//if order processing status is finalized or canceled, cleanup the reservation
				if ($processingStatus == Order::PROCESSING_STATUS_FINALIZED || $processingStatus == Order::PROCESSING_STATUS_CANCELED)
				{
					$stockManager->unsetReservations($order->getIdentifier());
				}
			}
		}
		else
		{
			$event->getApplication()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
		}
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	public function sendMail(\Change\Job\Event $event)
	{
		$job = $event->getJob();

		$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($job->getArgument('orderId'));
		if ($order instanceof Order)
		{
			// Send mail to confirm the order creation
			$webstore = $order->getWebStoreIdInstance();
			if ($webstore)
			{
				$orderProcess = $webstore->getOrderProcess();
				if ($orderProcess)
				{
					if ($orderProcess->getSendMailOrderCanceled())
					{
						$processingStatus = $order->getProcessingStatus();
						//if order processing status is canceled, send notification mail
						if ($processingStatus == Order::PROCESSING_STATUS_CANCELED)
						{
							$context = $order->getContext();
							$transactionId = $context->get('transactionId');

							$documentManager = $event->getApplicationServices()->getDocumentManager();

							/** @var \Rbs\Payment\Documents\Transaction $transaction */
							$transaction = $documentManager->getDocumentInstance($transactionId);
							if ($transaction)
							{
								$contextData = $transaction->getContextData();
								if (isset($contextData['websiteId']) && isset($contextData['LCID']))
								{
									/** @var $website \Rbs\Website\Documents\Website */
									$website = $documentManager->getDocumentInstance($contextData['websiteId']);

									if ($website)
									{
										$owner = $order->getOwnerIdInstance();
										$userEmail = $order->getEmail();
										$fullName = '';
										if ($owner instanceof \Rbs\User\Documents\User)
										{
											// Get Fullname
											$profileManager = $event->getApplicationServices()->getProfileManager();
											$u = new \Rbs\User\Events\AuthenticatedUser($owner);
											$profile = $profileManager->loadProfile($u, 'Rbs_User');
											$fullName = $profile->getPropertyValue('fullName');
											if ($fullName)
											{
												$fullName = ' ' . $fullName;
											}

											$userEmail = $owner->getEmail();
										}

										// Send email to confirm creation
										$LCID = $contextData['LCID'];

										$documentManager->pushLCID($LCID);

										/* @var \Rbs\Generic\GenericServices $genericServices */
										$genericServices = $event->getServices('genericServices');
										$mailManager = $genericServices->getMailManager();
										try
										{
											$commerceServices = $event->getServices('commerceServices');
											if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
											{
												$pm = $commerceServices->getPriceManager();
												$currency = $order->getCurrencyCode();
												$total = $pm->formatValue($order->getPaymentAmountWithTaxes(), $currency);
											}
											else
											{
												$total = $order->getPaymentAmountWithTaxes();
											}

											$mailManager->send('rbs_commerce_order_canceled', $website, $LCID, $userEmail,
												["website" => $website->getTitle(), "fullname" => $fullName, "total" => $total, "orderCode" => $order->getCode(), "orderId" => $order->getId()]);
										}
										catch (\RuntimeException $e)
										{
											$event->getApplicationServices()->getLogging()->info($e);
										}
										$documentManager->popLCID();
									}
								}
							}
						}
					}
				}
			}
		}
	}
} 
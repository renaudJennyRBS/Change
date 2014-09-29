<?php
/**
 * Copyright (C) 2014 Loïc Couturier
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Job;

/**
 * @name \Rbs\Commerce\Job\ProcessTransactionalNotification
 */
class ProcessTransactionalNotification
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();

		if ($job->getArgument('notificationName') == 'rbs_commerce_order_shipment_under_preparation')
		{
			$this->rbsCommerceOrderShipmentUnderPreparation($event);
		}
		if ($job->getArgument('notificationName') == 'rbs_commerce_order_shipment_sent')
		{
			$this->rbsCommerceOrderShipmentSent($event);
		}
		if ($job->getArgument('notificationName') == 'rbs_commerce_order_confirmation')
		{
			$this->rbsCommerceOrderConfirmation($event);
		}
		if ($job->getArgument('notificationName') == 'rbs_commerce_order_canceled')
		{
			$this->rbsCommerceOrderCanceled($event);
		}

		$event->success();
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderShipmentUnderPreparation(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$targetId = $job->getArgument('targetId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Shipment $shipment */
		$shipment = $documentManager->getDocumentInstance($targetId, 'Rbs_Order_Shipment');

		if ($shipment)
		{
			/** @var \Rbs\Order\Documents\Order $order */
			$order = $shipment->getOrderIdInstance();
			if ($order != null)
			{
				// Send mail to confirm the order creation
				$webStore = $order->getWebStoreIdInstance();
				if ($webStore)
				{
					$orderProcess = $webStore->getOrderProcess();
					if ($orderProcess)
					{
						if ($orderProcess->getSendMailShipmentPreparation())
						{
							$context = $order->getContext();
							$transactionId = $context->get('transactionId');

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
											$mailManager->send('rbs_commerce_order_shipment_under_preparation', $website, $LCID, $userEmail,
												["website" => $website->getTitle(), "fullname" => $fullName, "orderCode" => $order->getCode(), "orderId" => $order->getId(),
													"shipmentCode" => $shipment->getCode(), "shipmentId" => $shipment->getId()]);
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

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderShipmentSent(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$targetId = $job->getArgument('targetId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Shipment $shipment */
		$shipment = $documentManager->getDocumentInstance($targetId, 'Rbs_Order_Shipment');

		if ($shipment && $shipment->getPrepared() && $shipment->getShippingDate() != null)
		{
			/** @var \Rbs\Order\Documents\Order $order */
			$order = $shipment->getOrderIdInstance();
			if ($order != null)
			{
				// Send mail to confirm the order creation
				$webStore = $order->getWebStoreIdInstance();
				if ($webStore)
				{
					$orderProcess = $webStore->getOrderProcess();
					if ($orderProcess)
					{
						if ($orderProcess->getSendMailShipmentFinalized())
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
											$mailManager->send('rbs_commerce_order_shipment_sent', $website, $LCID, $userEmail,
												["website" => $website->getTitle(), "fullname" => $fullName, "orderCode" => $order->getCode(), "orderId" => $order->getId(), "shipmentCode" => $shipment->getCode(),
													"shipmentId" => $shipment->getId()],
												$shipment->getShippingDate());
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

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderConfirmation(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$targetId = $job->getArgument('targetId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Order\Documents\Order $order */
		$order = $documentManager->getDocumentInstance($targetId, 'Rbs_Order_Order');
		if ($order != null)
		{
			// Send mail to confirm the order creation
			$webstore = $order->getWebStoreIdInstance();
			if ($webstore)
			{
				$orderProcess = $webstore->getOrderProcess();
				if ($orderProcess)
				{
					if ($orderProcess->getSendMailOrderConfirm())
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
											$total = $pm->formatValue($order->getPaymentAmount(), $currency);
										}
										else
										{
											$total = $order->getPaymentAmount();
										}

										$mailManager->send('rbs_commerce_order_confirmation', $website, $LCID, $order->getEmail(),
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

	/**
	 * @param \Change\Job\Event $event
	 */
	protected function rbsCommerceOrderCanceled(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($job->getArgument('orderId'));
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			// Send mail to confirm the order creation
			$webStore = $order->getWebStoreIdInstance();
			if ($webStore)
			{
				$orderProcess = $webStore->getOrderProcess();
				if ($orderProcess)
				{
					if ($orderProcess->getSendMailOrderCanceled())
					{
						$processingStatus = $order->getProcessingStatus();
						//if order processing status is canceled, send notification mail
						if ($processingStatus == \Rbs\Order\Documents\Order::PROCESSING_STATUS_CANCELED)
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
												$total = $pm->formatValue($order->getPaymentAmount(), $currency);
											}
											else
											{
												$total = $order->getPaymentAmount();
											}

											$mailManager->send('rbs_commerce_order_canceled', $website, $LCID, $userEmail,
												["website" => $website->getTitle(), "fullname" => $fullName, "total" => $total,
													"orderCode" => $order->getCode(), "orderId" => $order->getId()]);
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
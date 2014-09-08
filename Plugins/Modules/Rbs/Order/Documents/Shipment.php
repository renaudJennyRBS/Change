<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

use Change\Documents\Events;
use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Rbs\Order\Documents\Shipment
 */
class Shipment extends \Compilation\Rbs\Order\Documents\Shipment
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return 'Shipment:' . $this->getId();
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE), array($this, 'sendNotificationMailShipmentUnderPreparation'), 1);
		$eventManager->attach(array(DocumentEvent::EVENT_UPDATE), array($this, 'sendNotificationMailShipmentFinalized'), 1);
	}

	/**
	 * @param Events\Event $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		$commerceServices = $event->getServices('commerceServices');
		if ($this->getPrepared() && $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			if (!$this->getCode())
			{
				$this->setCode($commerceServices->getProcessManager()->getNewCode($this));
			}
			//if the shipment has just been prepared, decrement stock reservation
			if ($this->isPropertyModified('prepared'))
			{
				$this->decrementOrderReservation($commerceServices->getStockManager(), $event->getApplicationServices()->getDocumentManager());
			}
		}
	}

	/**
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	protected function decrementOrderReservation($stockManager, $documentManager)
	{
		//check if there is no previous movement for this shipment
		if (count($stockManager->getInventoryMovementsByTarget($this->getIdentifier())))
		{
			return;
		}
		$order = $this->getOrderId() != 0 ? $documentManager->getDocumentInstance($this->getOrderId()) : null;
		foreach ($this->getData() as $data)
		{
			if (isset($data['codeSKU']) && isset($data['quantity']))
			{
				$sku = $stockManager->getSkuByCode($data['codeSKU']);
				if ($sku)
				{
					$stockManager->addInventoryMovement(-$data['quantity'], $sku, null, new \DateTime(), $this->getIdentifier());
					if ($order instanceof \Rbs\Order\Documents\Order)
					{
						$stockManager->decrementReservation($order->getIdentifier(), $sku->getId(), $data['quantity']);
					}
				}
			}
			else
			{
				$this->getApplication()->getLogging()->error('Invalid shipment, SKU or quantity on data not set');
			}
		}
	}

	/**
	 * @param Events\Event $event
	 */
	public function onDefaultUpdateRestResult(DocumentEvent $event)
	{
		parent::onDefaultUpdateRestResult($event);

		if ($event->getDocument() !== $this)
		{
			return;
		}

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			if (!$this->getCode())
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$restResult->setProperty('label', $i18nManager->trans('m.rbs.order.admin.code_waiting', ['ucf']));
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$linkResult = $restResult;
			if (!$linkResult->getProperty('code'))
			{
				$linkResult->setProperty('code', $linkResult->getProperty('label'));
			}
		}
	}

	/**
	 * @param Events\Event $event
	 */
	public function sendNotificationMailShipmentUnderPreparation(DocumentEvent $event)
	{
		/** @var \Rbs\Order\Documents\Order $order */
		$order = $this->getOrderIdInstance();
		if ($order != null)
		{
			// Send mail to confirm the order creation
			$webstore = $order->getWebStoreIdInstance();
			if ($webstore)
			{
				$orderProcess = $webstore->getOrderProcess();
				if ($orderProcess)
				{
					if ($orderProcess->getSendMailShipmentPreparation())
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
										$mailManager->send('rbs_commerce_order_shipment_under_preparation', $website, $LCID, $userEmail,
											["website" => $website->getTitle(), "fullname" => $fullName, "orderCode" => $order->getCode(), "orderId" => $order->getId(), "shipmentCode" => $this->getCode(), "shipmentId" => $this->getId()]);
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
	 * @param Events\Event $event
	 */
	public function sendNotificationMailShipmentFinalized(DocumentEvent $event)
	{
		if ($this->getPrepared() && $this->isPropertyModified('prepared'))
		{
			/** @var \Rbs\Order\Documents\Order $order */
			$order = $this->getOrderIdInstance();
			if ($order != null)
			{
				// Send mail to confirm the order creation
				$webstore = $order->getWebStoreIdInstance();
				if ($webstore)
				{
					$orderProcess = $webstore->getOrderProcess();
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
												["website" => $website->getTitle(), "fullname" => $fullName, "orderCode" => $order->getCode(), "orderId" => $order->getId(), "shipmentCode" => $this->getCode(), "shipmentId" => $this->getId()]);
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

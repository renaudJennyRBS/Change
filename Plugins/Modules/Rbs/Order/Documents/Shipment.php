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
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context = null)
	{
		$this->context = new \Zend\Stdlib\Parameters();
		if (is_array($context))
		{
			$this->context->fromArray($context);
		}
		elseif ($context instanceof \Traversable)
		{
			foreach ($context as $n => $v)
			{
				$this->context->set($n, $v);
			}
		}
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$this->setContext($this->getContextData());
		}
		return $this->context;
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
			// If the shipment has just been prepared, decrement stock reservation.
			if ($this->isPropertyModified('prepared'))
			{
				$this->decrementOrderReservation($commerceServices->getStockManager(), $event->getApplicationServices()->getDocumentManager());
			}
		}

		if ($this->context instanceof \Zend\Stdlib\Parameters)
		{
			$this->setContextData($this->context->toArray());
			$this->context = null;
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

			$context = $this->getContext()->toArray();
			$restResult->setProperty('context', (count($context)) ? $context : null);

			$lines = [];
			foreach ($this->getLines() as $line)
			{
				$lines[] = $line->toArray();
			}
			$restResult->setProperty('lines', $lines);
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
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch($name)
		{
			case 'context':
				$this->setContext($value);
				break;

			case 'lines':
				$this->setLines($value);
				break;

			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
	}

	/**
	 * @param Events\Event $event
	 */
	public function sendNotificationMailShipmentUnderPreparation(DocumentEvent $event)
	{
		$jobManager = $event->getApplicationServices()->getJobManager();
		$argument = ['notificationName' => 'rbs_commerce_order_shipment_under_preparation', 'targetId' => $this->getId()];
		$jobManager->createNewJob('Rbs_Notification_ProcessTransactionalNotification', $argument);
	}

	/**
	 * @param Events\Event $event
	 */
	public function sendNotificationMailShipmentFinalized(DocumentEvent $event)
	{
		$jobManager = $event->getApplicationServices()->getJobManager();
		$argument = ['notificationName' => 'rbs_commerce_order_shipment_sent', 'targetId' => $this->getId()];
		$jobManager->createNewJob('Rbs_Notification_ProcessTransactionalNotification', $argument);
	}

	/**
	 * @var \Rbs\Order\Shipment\Line[]|null
	 */
	protected $lines = null;

	/**
	 * @return \Rbs\Order\Shipment\Line[]
	 */
	public function getLines()
	{
		if ($this->lines === null)
		{
			$this->lines = [];
			$data = $this->getData();
			if (is_array($data))
			{
				foreach ($data as $lineData)
				{
					$this->lines[] = new \Rbs\Order\Shipment\Line($lineData);
				}
			}
		}
		return $this->lines;
	}

	/**
	 * @param \Rbs\Order\Shipment\Line[]|array[] $lines
	 * @return $this
	 */
	public function setLines($lines)
	{
		$this->lines = [];
		$data = [];
		if (is_array($lines))
		{
			foreach ($lines as $line)
			{
				$l = new \Rbs\Order\Shipment\Line($line);
				$this->lines[] = $l;
				$data[] = $l->toArray();
			}
		}
		$this->setData(count($data) ? $data : null);
		return $this;
	}
}

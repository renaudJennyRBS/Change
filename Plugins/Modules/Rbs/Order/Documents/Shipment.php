<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

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
		if ($this->getCode())
		{
			return $this->getCode();
		}
		return 'NO-CODE-DEFINED';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_UPDATED, [$this, 'onUpdated'], 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @throws \RuntimeException
	 */
	public function onUpdated($event)
	{
		$shipment = $event->getDocument();
		if ($shipment instanceof \Rbs\Order\Documents\Shipment)
		{
			if ($shipment->getPrepared())
			{
				$commerceServices = $event->getServices('commerceServices');
				if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
				{
					$stockManager = $commerceServices->getStockManager();
				}
				else
				{
					throw new \RuntimeException('CommerceServices not set', 999999);
				}

				$this->decrementOrderReservation($shipment, $stockManager, $event->getApplicationServices()->getDocumentManager());
			}
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Shipment $shipment
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @throws \RuntimeException
	 */
	protected function decrementOrderReservation($shipment, $stockManager, $documentManager)
	{
		foreach ($shipment->getData() as $data)
		{
			if (isset($data['SKU']) && isset($data['quantity']))
			{
				$sku = $documentManager->getDocumentInstance($data['SKU']);
				if ($sku instanceof \Rbs\Stock\Documents\Sku)
				{
					$stockManager->addInventoryMovement($data['quantity'], $sku);
					$order = $shipment->getOrderId() != 0 ? $documentManager->getDocumentInstance($shipment->getOrderId()) : null;
					if ($order instanceof \Rbs\Order\Documents\Order)
					{
						$stockManager->decrementReservation($order->getIdentifier(), $sku->getId(), $data['quantity']);
					}
				}
			}
			else
			{
				throw new \RuntimeException('Invalid shipment, SKU or quantity on data not set', 999999);
			}
		}
	}
}

<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Http\Rest\Actions;

/**
 * @name \Rbs\Order\Http\Rest\Actions\OrderRemainder
 */
class OrderRemainder
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		if ($request->isGet())
		{
			$orderId = $request->getQuery('orderId');
			$shippingModeId = $request->getQuery('shippingModeId');
			$params = $this->getParams($orderId, $shippingModeId, $event);
			if (count($params) !== 2)
			{
				$result = new \Change\Http\Rest\V1\ArrayResult();
				$result->setArray(['status' => 'unavailable', 'remainLines' => [], 'address' => null]);
				$event->setResult($result);
				return;
			}

			// Status can be:
			//  'noShipment' if there is no shipment at all
			//  'remain' at least one shipment is done but not all of them
			//  'sent' if there is no remain
			//  'unavailable' if there is no shippingMode
			$status = 'noShipment';
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $order \Rbs\Order\Documents\Order */
			$order = $params['order'];
			$orderShippingMode = null;
			foreach ($order->getShippingModes() as $mode)
			{
				if ($shippingModeId == $mode->getId())
				{
					$orderShippingMode = $mode;
					break;
				}
			}

			if (!$orderShippingMode)
			{
				$result = new \Change\Http\Rest\V1\ArrayResult();
				$result->setArray(['status' => 'unavailable', 'remainLines' => [], 'address' => null]);
				$event->setResult($result);
				return;
			}
			$address = $orderShippingMode->getAddress()->toArray();

			/** @var \Rbs\Commerce\CommerceServices $commerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$skuData = $this->getSkuDataFromLines($order->getLines(), $orderShippingMode);
			// Keep original quantity information to seek if a shipment is already done.
			$skuOrderQuantity = [];
			foreach ($skuData as $codeSKU => $line)
			{
				$skuOrderQuantity[$codeSKU] = $line->getQuantity();
			}

			// Not prepared expeditions are excluded to no take care about the the expedition that we are editing...
			$dqb = $documentManager->getNewQuery('Rbs_Order_Shipment');
			$dqb->andPredicates($dqb->eq('orderId', $orderId), $dqb->eq('prepared', true));
			$shipments = $dqb->getDocuments();
			foreach ($shipments as $shipment)
			{
				/* @var $shipment \Rbs\Order\Documents\Shipment */
				foreach ($shipment->getLines() as $shipmentLine)
				{
					$codeSKU = $shipmentLine->getCodeSKU();
					if ($codeSKU && isset($skuData[$codeSKU]))
					{
						$skuData[$codeSKU]->setQuantity($skuData[$codeSKU]->getQuantity() - $shipmentLine->getQuantity());
					}
				}
			}

			$remainLines = [];
			$itemForShippingModeCount = 0;
			foreach ($skuData as $codeSku => $skuLine)
			{
				$itemForShippingModeCount++;
				// Try to find if the original order quantity differs from the remain, in this case
				// that mean at least a shipment is already done for this mode.
				if ($skuOrderQuantity[$codeSku] !== $skuLine->getQuantity())
				{
					$status = 'remain';
				}
				if ($skuLine->getQuantity() > 0)
				{
					$remainLine = $skuLine->toArray();
					$sku = $commerceServices->getStockManager()->getSKUByCode($codeSku);
					$remainLine['allowQuantitySplit'] = $sku ? $sku->getAllowQuantitySplit() : true;
					$remainLines[] = $remainLine;
				}
			}

			if ($itemForShippingModeCount === 0)
			{
				// If we cannot find any item for this shippingMode we cannot be clear concerning the status.
				$status = 'unavailable';
			}
			elseif (!count($remainLines))
			{
				$status = 'sent';
			}

			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setArray(['status' => $status, 'remainLines' => $remainLines, 'address' => $address]);
			$event->setResult($result);
		}
	}

	/**
	 * @param integer $orderId
	 * @param integer $shippingModeId
	 * @param \Change\Http\Event $event
	 * @return array
	 */
	protected function getParams($orderId, $shippingModeId, $event)
	{
		$params = [];
		if ($orderId)
		{
			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($orderId);
			if ($order instanceof \Rbs\Order\Documents\Order)
			{
				$params['order'] = $order;
			}
		}
		if ($shippingModeId)
		{
			$shippingMode = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shippingModeId);
			if ($shippingMode instanceof \Rbs\Shipping\Documents\Mode)
			{
				$params['shippingMode'] = $shippingMode;
			}
		}
		return $params;
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 * @param \Rbs\Commerce\Process\BaseShippingMode $shippingMode
	 * @return \Rbs\Order\Shipment\Line[]
	 */
	protected function getSkuDataFromLines($lines, $shippingMode)
	{
		/** @var \Rbs\Order\Shipment\Line[] $skuData */
		$skuData = [];
		foreach ($lines as $line)
		{
			$key = $line->getKey();
			if (!in_array($key, $shippingMode->getLineKeys()))
			{
				continue;
			}

			for ($i = 0; $i < count($line->getItems()); $i++)
			{
				$item = $line->getItems()[$i];
				$codeSku = $item->getCodeSKU();
				if ($codeSku)
				{
					$shipmentLine = new \Rbs\Order\Shipment\Line($line);
					if ($i > 0)
					{
						$shipmentLine->setCodeSKU($codeSku);
						$shipmentLine->setQuantity(max(1, $item->getReservationQuantity()) * $line->getQuantity());
					}

					if (isset($skuData[$codeSku]))
					{
						$skuData[$codeSku]->setQuantity($skuData[$codeSku]->getQuantity() + $shipmentLine->getQuantity());
					}
					else
					{
						$skuData[$codeSku] = $shipmentLine;
					}
				}
			}
		}
		return $skuData;
	}
}
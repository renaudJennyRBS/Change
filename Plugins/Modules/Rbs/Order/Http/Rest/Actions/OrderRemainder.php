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

			if (count($params['errors']) === 0)
			{
				//status can be:
				//  'noShipment' if there is no shipment at all
				//  'remain' at least one shipment is done but not all of them
				//  'sent' if there is no remain
				//  'unavailable' if there is no shippingMode
				$status = 'noShipment';
				$documentManager = $event->getApplicationServices()->getDocumentManager();

				/* @var $order \Rbs\Order\Documents\Order */
				$order = $params['order'];
				$shippingModes = $order->getShippingModes();

				$lines = $order->getLines();
				$commerceServices = $event->getServices('commerceServices');
				if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
				{
					throw new \RuntimeException(999999, 'Commerce services not set');
				}

				$skuData = $this->getSkuDataFromLines($lines, $shippingModes);
				//keep original quantity information to seek if a shipment is already done
				$skuOrderQuantity = [];
				foreach ($skuData as $codeSKU => $line)
				{
					$skuOrderQuantity[$codeSKU] = $line['quantity'];
				}

				$dqb = $documentManager->getNewQuery('Rbs_Order_Shipment');
				$dqb->andPredicates($dqb->eq('orderId', $orderId), $dqb->eq('prepared', true));
				$shipments = $dqb->getDocuments();
				foreach ($shipments as $shipment)
				{
					/* @var $shipment \Rbs\Order\Documents\Shipment */
					$shipmentLines = $shipment->getData();
					foreach ($shipmentLines as $shipmentLine)
					{
						$codeSKU = $shipmentLine['codeSKU'];
						if ($codeSKU && isset($skuData[$codeSKU]))
						{
							$skuData[$codeSKU]['quantity'] -= $shipmentLine['quantity'];
						}
					}
				}

				$address = null;
				$remainLines = [];
				$itemForShippingModeCount = 0;
				foreach ($skuData as $codeSku => $skuLine)
				{
					if (isset($skuLine['shippingModeId']) && $skuLine['shippingModeId'] == $shippingModeId)
					{
						$itemForShippingModeCount++;
						//try to find if the original order quantity differs from the remain, in this case
						//that mean at least a shipment is already done for this mode
						if ($skuOrderQuantity[$codeSku] !== $skuLine['quantity'])
						{
							$status = 'remain';
						}
						if ($skuLine['quantity'] > 0)
						{
							$remainLine = [
								'designation' => $skuLine['designation'],
								'quantity' => $skuLine['quantity'],
								'codeSKU' => $codeSku,
								'allowQuantitySplit' => true
							];
							$sku = $commerceServices->getStockManager()->getSKUByCode($codeSku);
							if ($sku)
							{
								$remainLine['SKU'] = $sku->getId();
								$remainLine['allowQuantitySplit'] = $sku->getAllowQuantitySplit();
							}
							$remainLines[] = $remainLine;
						}
						$address = isset($skuLine['address']) ? $skuLine['address'] : $address;
					}
				}

				if ($itemForShippingModeCount === 0)
				{
					//if we cannot find any item for this shippingMode we cannot be clear concerning the status
					$status = 'unavailable';
				}
				elseif (!count($remainLines))
				{
					$status = 'sent';
				}

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray(['status' => $status, 'remainLines' => $remainLines, 'address' => $address]);
				$event->setResult($result);
			}
			else
			{
				$result = new \Change\Http\Rest\Result\ErrorResult(999999, implode(', ', $params['errors']));
				$event->setResult($result);
			}
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
		$params['errors'] = [];
		if (!$orderId)
		{
			$params['errors'][] = 'Empty argument orderId';
		}
		else
		{
			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($orderId);
			if ($order instanceof \Rbs\Order\Documents\Order)
			{
				$params['order'] = $order;
			}
			else
			{
				$params['errors'][] = 'Invalid argument, orderId doesn\'t match any order';
			}
		}
		if (!$shippingModeId)
		{
			$params['errors'][] = 'Empty argument shippingModeId';
		}
		else
		{
			$shippingMode = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shippingModeId);
			if ($shippingMode instanceof \Rbs\Shipping\Documents\Mode)
			{
				$params['shippingMode'] = $shippingMode;
			}
			else
			{
				$params['errors'][] = 'Invalid argument, shippingModeId doesn\'t match any order';
			}
		}
		return $params;
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 * @param \Rbs\Commerce\Process\BaseShippingMode[] $shippingModes
	 * @return array
	 */
	protected function getSkuDataFromLines($lines, $shippingModes)
	{
		$skuData = [];
		foreach ($lines as $line)
		{
			$key = $line->getKey();
			foreach ($line->getItems() as $item)
			{
				$codeSKU = $item->getCodeSKU();
				if (isset($skuData[$codeSKU]))
				{
					$skuData[$codeSKU]['quantity'] += $item->getReservationQuantity() * $line->getQuantity();
				}
				else
				{
					$skuData[$codeSKU] = [
						'quantity' => $item->getReservationQuantity() * $line->getQuantity(),
						'designation' => $line->getDesignation()
					];

					foreach ($shippingModes as $shippingMode)
					{
						if (in_array($key, $shippingMode->getLineKeys()))
						{
							$skuData[$codeSKU]['shippingModeId'] = $shippingMode->getId();
							if ($shippingMode->getAddress())
							{
								$skuData[$codeSKU]['address'] = $shippingMode->getAddress()->toArray();
							}
							else {
								$skuData[$codeSKU]['address'] = null;
							}
							break;
						}
					}
				}
			}
		}
		return $skuData;
	}
}
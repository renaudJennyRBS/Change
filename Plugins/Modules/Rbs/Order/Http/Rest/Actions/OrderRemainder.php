<?php
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

				/* @var $order \Rbs\Order\Documents\Order */
				$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($orderId);
				$shippingData = $order->getShippingData();
				$linesData = $order->getLinesData();

				$skuData = $this->getSkuDataFromLines($linesData, $shippingData);
				//keep original quantity information to seek if a shipment is already done
				$skuOrderQuantity = [];
				foreach ($skuData as $codeSKU => $line)
				{
					$skuOrderQuantity[$codeSKU] = $line['quantity'];
				}

				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Order_Shipment');
				$dqb->andPredicates($dqb->eq('orderId', $orderId), $dqb->eq('prepared', true));
				$shipments = $dqb->getDocuments();
				foreach ($shipments as $shipment)
				{
					/* @var $shipment \Rbs\Order\Documents\Shipment */
					$shipmentLines = $shipment->getData();
					foreach ($shipmentLines as $shipmentLine)
					{
						/* @var $sku \Rbs\Stock\Documents\Sku */
						$sku = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shipmentLine['SKU']);
						if (isset($skuData[$sku->getCode()]))
						{
							$skuData[$sku->getCode()]['quantity'] -= $shipmentLine['quantity'];
						}
					}
				}

				$address = ['address' => new \stdClass(), 'addressFields' => 0];
				$remainLines = [];
				$itemForShippingModeCount = 0;
				foreach ($skuData as $codeSku => $skuLine)
				{
					if (isset($skuLine['shippingModeId']) && $skuLine['shippingModeId'] === $shippingModeId)
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
							$sku = $this->getSKUByCode($codeSku, $event->getApplicationServices()->getDocumentManager());
							$remainLines[] = [
								'designation' => $skuLine['designation'],
								'quantity' => $skuLine['quantity'],
								'codeSKU' => $codeSku,
								'allowQuantitySplit' => $sku->getAllowQuantitySplit(),
								'SKU' => $sku->getId()
							];
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
	 * @param string $code
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\Stock\Documents\Sku
	 */
	protected function getSKUByCode($code, $documentManager)
	{
		$dqb = $documentManager->getNewQuery('Rbs_Stock_Sku');
		$dqb->andPredicates($dqb->eq('code', $code));
		return $dqb->getFirstDocument();
	}

	/**
	 * @param array $linesData
	 * @param array $shippingData
	 * @return array
	 */
	protected function getSkuDataFromLines($linesData, $shippingData)
	{
		$skuData = [];
		foreach ($linesData as $line)
		{
			//TODO use lineKey instead of index after order refactoring
			$index = $line['index'];
			foreach ($line['items'] as $item)
			{
				$codeSKU = $item['codeSKU'];
				$skuData[$codeSKU] = [
					'quantity' => $item['reservationQuantity'] * $line['quantity'],
					'designation' => $line['designation']
				];

				foreach ($shippingData as $shippingInfo)
				{
					if (in_array($index, $shippingInfo['lines']))
					{
						$skuData[$codeSKU]['shippingModeId'] = $shippingInfo['id'];
						$address = isset($shippingInfo['address']) ? $shippingInfo['address'] : new \stdClass();
						$addressFields = isset($shippingInfo['addressFields']) ? $shippingInfo['addressFields'] : 0;
						$skuData[$codeSKU]['address'] =  [
							'address' => $address,
							'addressFields' => $addressFields
						];
						break;
					}
				}
			}
		}

		return $skuData;
	}
}
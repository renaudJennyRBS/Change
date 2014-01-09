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
				$skuData = [];
				$skuOrderQuantity = []; //keep original quantity information to seek if a shipment is already done
				foreach ($order->getLines() as $line)
				{
					$options = $line->getOptions();
					$lineShippingModeId = isset($options['shippingMode']) ? $options['shippingMode'] : null;
					foreach ($line->getItems() as $item)
					{
						$skuData[$item->getCodeSKU()] = [
							'quantity' => $item->getQuantity() * $line->getQuantity(),
							'designation' => $line->getDesignation()
						];
						$skuOrderQuantity[$item->getCodeSKU()] = $item->getQuantity() * $line->getQuantity();
						if ($lineShippingModeId)
						{
							$skuData[$item->getCodeSKU()]['shippingModeId'] = $lineShippingModeId;
							//search shipping address
							foreach ($order->getShippingData() as $shippingData)
							{
								if (in_array($options['lineNumber'], $shippingData['lines']))
								{
									$skuData[$item->getCodeSKU()]['address'] =  [
										'address' => isset($shippingData['address']) ? $shippingData['address'] : new \stdClass(),
										'addressFields' => isset($shippingData['addressFields']) ? $shippingData['addressFields'] : 0
									];
									break;
								}
							}
						}
					}
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
}
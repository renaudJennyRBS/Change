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
				$status = 'noShipment';

				/* @var $order \Rbs\Order\Documents\Order */
				$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($orderId);
				$skuQuantity = [];
				$skuOrderQuantity = []; //keep original quantity information to seek if a shipment is already done
				foreach ($order->getLines() as $line)
				{
					$options = $line->getOptions();
					$lineShippingModeId = isset($options['shippingMode']) ? $options['shippingMode'] : null;
					foreach ($line->getItems() as $item)
					{
						$skuQuantity[$item->getCodeSKU()] = [
							'quantity' => $item->getQuantity() * $line->getQuantity(),
							'designation' => $line->getDesignation()
						];
						$skuOrderQuantity[$item->getCodeSKU()] = $item->getQuantity() * $line->getQuantity();
						if ($lineShippingModeId)
						{
							$skuQuantity[$item->getCodeSKU()]['shippingModeId'] = $lineShippingModeId;
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
						$skuQuantity[$sku->getCode()]['quantity'] -= $shipmentLine['quantity'];
					}
				}

				$remainLines = [];
				foreach ($skuQuantity as $codeSku => $skuLine)
				{
					if ($skuLine['shippingModeId'] === $shippingModeId)
					{
						//try to find if the original order quantity differs from the remain, in this case
						//that mean at least a shipment is already done for this mode
						if ($skuOrderQuantity[$codeSku] !== $skuLine['quantity'])
						{
							$status = 'remain';
						}
						if ($skuLine['quantity'] !== 0)
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
					}
				}

				if (!count($remainLines))
				{
					$status = 'sent';
				}

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray(['status' => $status, 'remainLines' => $remainLines]);
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
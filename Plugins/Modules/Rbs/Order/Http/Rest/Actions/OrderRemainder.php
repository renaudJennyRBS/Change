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
				/* @var $order \Rbs\Order\Documents\Order */
				$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($orderId);
				$skuQuantity = [];
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
						if ($lineShippingModeId)
						{
							$skuQuantity[$item->getCodeSKU()]['shippingModeId'] = $lineShippingModeId;
						}
					}
				}

				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Order_Expedition');
				$dqb->andPredicates($dqb->eq('orderId', $orderId), $dqb->eq('prepared', true));
				$expeditions = $dqb->getDocuments();
				foreach ($expeditions as $expedition)
				{
					/* @var $expedition \Rbs\Order\Documents\Expedition */
					$expeditionLines = $expedition->getData();
					foreach ($expeditionLines as $expeditionLine)
					{
						/* @var $sku \Rbs\Stock\Documents\Sku */
						$sku = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($expeditionLine['SKU']);
						$skuQuantity[$sku->getCode()]['quantity'] -= $expeditionLine['quantity'];
					}
				}

				$remainLines = [];
				foreach ($skuQuantity as $codeSku => $skuLine)
				{
					if ($skuLine['quantity'] !== 0 && $skuLine['shippingModeId'] === $shippingModeId)
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

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray($remainLines);
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
<?php
namespace Rbs\Payment\Http\Rest;

/**
 * @name \Rbs\Payment\Http\Rest\UpdateProcessingStatusForTransaction
 */
class UpdateProcessingStatusForTransaction
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function validatePayment(\Change\Http\Event $event)
	{
		$this->updateProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_SUCCESS, $event);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function refusePayment(\Change\Http\Event $event)
	{
		$this->updateProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_FAILED, $event);
	}

	/**
	 * @param string $status
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	protected function updateProcessingStatus($status, \Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			throw new \RuntimeException('Unable to get CommerceServices', 999999);
		}

		$transactionId = $event->getParam('documentId');
		$processingInfos = $event->getParam('processingInfos');
		$params = $this->getParams($transactionId, $status, $processingInfos, $event);
		if (count($params['errors']) === 0)
		{
			/* @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $params['transaction'];
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$transaction->save();
				$paymentManager = $commerceServices->getPaymentManager();

				// In case where the transaction is turned directly from initiated to new status.
				if ($params['originalStatus'] === \Rbs\Payment\Documents\Transaction::STATUS_INITIATED)
				{
					$paymentManager->handleProcessingForTransaction($transaction);
				}

				$paymentManager->{'handle' . ucfirst($status) . 'ForTransaction'}($transaction);

				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
			}

			$event->setParam('modelName', $transaction->getDocumentModelName());
			$docAction = new \Change\Http\Rest\Actions\GetDocument();
			$docAction->execute($event);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, implode(', ', $params['errors']));
			$event->setResult($result);
		}
	}

	/**
	 * @param integer $transactionId
	 * @param string $status
	 * @param array $processingInfos
	 * @param \Change\Http\Event $event
	 * @return array
	 */
	protected function getParams($transactionId, $status, $processingInfos, $event)
	{
		$params = [];
		$params['errors'] = [];
		if (!$transactionId)
		{
			$params['errors'][] = 'Empty argument transactionId';
		}
		else
		{
			$transaction = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($transactionId);
			if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
			{
				$params['transaction'] = $transaction;
			}
			else
			{
				$params['errors'][] = 'Invalid argument, transactionId doesn\'t match any transaction';
			}

			$params['originalStatus'] = $transaction->getProcessingStatus();
			$transaction->setProcessingStatus($status);

			$processingInfos += ['processingIdentifier' => null, 'processingDate' => null, 'processingData' => null];

			$processingIdentifier = $processingInfos['processingIdentifier'];
			if ($processingIdentifier !== null)
			{
				$transaction->setProcessingIdentifier(is_string($processingIdentifier) ? $processingIdentifier : null);
			}

			$processingDate = $processingInfos['processingDate'];
			if ($processingDate !== null || !$transaction->getProcessingDate())
			{
				$transaction->setProcessingDate(new \DateTime((!$processingDate) ? $processingDate : null));
			}

			$processingData = $processingInfos['processingData'];
			if ($processingData !== null)
			{
				$transaction->setProcessingData((!is_array($processingData)) ? $processingData : null);
			}
		}
		return $params;
	}
}
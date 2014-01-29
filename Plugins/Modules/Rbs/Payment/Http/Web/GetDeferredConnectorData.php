<?php
namespace Rbs\Payment\Http\Web;

/**
 * @name \Rbs\Payment\Http\Web\GetDeferredConnectorData
 */
class GetDeferredConnectorData extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (!isset($arguments['connectorId']) || !isset($arguments['transactionId']))
		{
			throw new \RuntimeException('Invalid parameters', 999999);
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$connector = $documentManager->getDocumentInstance($arguments['connectorId']);
		$transaction = $documentManager->getDocumentInstance($arguments['transactionId']);
		if (!($connector instanceof \Rbs\Payment\Documents\DeferredConnector))
		{
			throw new \RuntimeException('Invalid connector: ' . $connector, 999999);
		}
		if (!($transaction instanceof \Rbs\Payment\Documents\Transaction))
		{
			throw new \RuntimeException('Invalid transaction: ' . $transaction, 999999);
		}

		$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
		$richTextManager = $event->getApplicationServices()->getRichTextManager();

		$query = array('connectorId' => $connector->getId(), 'transactionId' => $transaction->getId());
		$instructions = $connector->getCurrentLocalization()->getInstructions();
		$data = array(
			'instructions' => $richTextManager->render($instructions, 'Website', $richTextContext),
			'paymentURL' => $event->getUrlManager()->getAjaxURL('Rbs_Payment', 'DeferredConnectorReturnSuccess', $query)
		);

		$result = $this->getNewAjaxResult($data);
		$event->setResult($result);
	}
}
<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Invoice
 */
class Invoice extends \Compilation\Rbs\Order\Documents\Invoice
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $invoice Invoice */
		$invoice = $event->getDocument();
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$linkResult = $restResult;
			if (!$linkResult->getProperty('code'))
			{
				$linkResult->setProperty('code', $linkResult->getProperty('label'));
			}

			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			$formattedAmount = $nf->formatCurrency($invoice->getAmountWithTax(), $invoice->getCurrencyCode());
			$restResult->setProperty('formattedAmountWithTax', $formattedAmount);
		}
	}
}

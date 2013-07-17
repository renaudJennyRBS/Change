<?php
namespace Rbs\Price\Http\Rest\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Price\Http\Rest\Actions\TaxInfo
 */
class TaxInfo
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isGet()) {
			$billingAreaId = $request->getQuery('id');
			if (intval($billingAreaId) > 0)
			{
				$billingArea = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($billingAreaId);
			}
		}
		$event->setResult($this->generateResult($billingArea, $event->getApplicationServices()->getI18nManager()->getLCID()));
	}

	/**
	 * @param \Rbs\Price\Documents\BillingArea $billingArea
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($billingArea, $locale)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();
		if ($billingArea === null)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_400);
		}
		else
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$data = array();
			$nf = new \NumberFormatter($locale, \NumberFormatter::PERCENT);
			$nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);
			foreach ($billingArea->getTaxes() as $tax)
			{
				$taxInfo = array('label' => $tax->getLabel(), 'code' => $tax->getCode(), 'categories' => array());
				foreach ($tax->getCategoryCodes() as $catCode)
				{
					$rate = $tax->getRate($catCode, $tax->getDefaultZone());
					$taxInfo['categories'][] = array('code' => $catCode, 'rate' => $rate, 'formattedRate' => $nf->format($rate));
				}
				$data[] = $taxInfo;
			}
			$result->setArray($data);
		}
		return $result;
	}
}
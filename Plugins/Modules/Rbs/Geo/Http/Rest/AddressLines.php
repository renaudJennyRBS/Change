<?php
namespace Rbs\Geo\Http\Rest;

use Zend\Http\Response;

/**
 * @name \Rbs\Geo\Http\Rest\AddressLines
 */
class AddressLines
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		if ($request->getMethod() === \Zend\Http\Request::METHOD_POST)
		{
			$addressData = $request->getPost('address');
			$addressFieldsId = $request->getPost('addressFieldsId');
			if (is_array($addressData))
			{
				$address = new \Rbs\Geo\Address\BaseAddress($addressData);
				$addressFields = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressFieldsId);
				if ($addressFields instanceof \Rbs\Geo\Documents\AddressFields)
				{
					$layout = $addressFields->getFieldsLayoutData();
					$address->setLayout($layout);
				}
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Geo_Country');
				$dqb->andPredicates($dqb->eq('code', $address->getCountryCode()));
				$country = $dqb->getFirstDocument();
				if ($country instanceof \Rbs\Geo\Documents\Country)
				{
					$i18n = $event->getApplicationServices()->getI18nManager();
					$address->setFieldValue('country', $i18n->trans($country->getI18nTitleKey()));
				}

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray($address->getLines());
				$result->setHttpStatusCode(Response::STATUS_CODE_200);
				$event->setResult($result);
			}
			else
			{
				$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'address given for address lines is not an array');
				$result->setHttpStatusCode(Response::STATUS_CODE_500);
				$event->setResult($result);
			}
		}
	}
}
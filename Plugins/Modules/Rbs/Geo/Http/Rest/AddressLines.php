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
			$address = null;
			if (is_array($addressData))
			{
				if (isset($addressData['id']) && is_numeric($addressData['id']))
				{
					$address = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressData['id']);
				}
				else
				{
					$address = new \Rbs\Geo\Address\BaseAddress($addressData);
				}
			}
			elseif (is_numeric($addressData))
			{
				$address = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressData);
			}

			if ($address instanceof \Rbs\Geo\Address\AddressInterface)
			{
				$genericServices = $event->getServices('genericServices');
				if ($genericServices instanceof \Rbs\Generic\GenericServices)
				{
					$lines = $genericServices->getGeoManager()->getFormattedAddress($address);
					$result = new \Change\Http\Rest\Result\ArrayResult();
					$result->setArray($lines);
					$result->setHttpStatusCode(Response::STATUS_CODE_200);
					$event->setResult($result);
				}
				else
				{
					$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'Generic services not found');
					$result->setHttpStatusCode(Response::STATUS_CODE_500);
					$event->setResult($result);
				}
			}
			else
			{
				$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'address given for address lines is not valid');
				$result->setHttpStatusCode(Response::STATUS_CODE_409);
				$event->setResult($result);
			}
		}
		else
		{
			$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_POST]));
		}
	}
}
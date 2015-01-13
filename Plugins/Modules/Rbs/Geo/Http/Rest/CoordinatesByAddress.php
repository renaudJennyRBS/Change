<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Rest;

use Zend\Http\Response;

/**
 * @name \Rbs\Geo\Http\Rest\CoordinatesByAddress
 */
class CoordinatesByAddress
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
					$searchLocation = $genericServices->getGeoManager()->getCoordinatesByAddress($address);
					if (count($searchLocation)) {
						$result = new \Change\Http\Rest\V1\ArrayResult();
						$result->setArray($searchLocation);
						$result->setHttpStatusCode(Response::STATUS_CODE_200);
						$event->setResult($result);
					}
					else
					{
						$result = new \Change\Http\Rest\V1\ErrorResult(999999, 'unable to find coordinate');
						$result->setHttpStatusCode(Response::STATUS_CODE_409);
						$event->setResult($result);
					}
				}
			}
			else
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999, 'address given for address lines is not valid');
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
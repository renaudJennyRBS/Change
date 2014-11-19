<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Http\Rest\Actions;

class GetRefundDataForReturn
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isGet())
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $return \Rbs\Productreturn\Documents\ProductReturn */
			$return = $documentManager->getDocumentInstance($request->getQuery('productReturnId'), 'Rbs_Productreturn_ProductReturn');
			if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999, 'Invalid product return');
				$event->setResult($result);
				return;
			}

			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$result->setArray($commerceServices->getReturnManager()->getRefundData($return, []));
			$event->setResult($result);
		}
	}
} 
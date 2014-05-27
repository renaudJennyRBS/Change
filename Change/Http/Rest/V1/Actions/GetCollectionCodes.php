<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Actions;

use Change\Http\Event;
use Change\Http\Request;
use Zend\Http\Response as HttpResponse;

/**
* @name \Change\Http\Rest\V1\Actions\GetCollectionCodes
*/
class GetCollectionCodes
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if (!$request->isGet())
		{
			$resolver = $event->getController()->getActionResolver();
			if ($resolver instanceof \Change\Http\Rest\V1\Resolver)
			{
				$result = $event->getController()->notAllowedError($request->getMethod(), array(Request::METHOD_GET));
				$event->setResult($result);
				return;
			}
			return;
		}

		$cm = $event->getApplicationServices()->getCollectionManager();
		$codes = $cm->getCodes($request->getQuery()->toArray());
		$event->setResult($this->generateResult($codes));
	}

	/**
	 * @param string[] $codes
	 * @return \Change\Http\Rest\V1\ArrayResult
	 */
	protected function generateResult($codes)
	{
		$result = new \Change\Http\Rest\V1\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setArray(array('codes' => $codes));
		return $result;
	}
}
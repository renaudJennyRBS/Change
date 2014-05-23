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
* @name \Change\Http\Rest\V1\Actions\GetCollectionItems
*/
class GetCollectionItems
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if (!$request->isGet())
		{
			$result = $event->getController()->notAllowedError($request->getMethod(), array(Request::METHOD_GET));
			$event->setResult($result);
			return;
		}

		$code = $request->getQuery('code');
		if (is_string($code) && !empty($code))
		{
			$cm = $event->getApplicationServices()->getCollectionManager();
			$parameters = $request->getQuery()->toArray();
			unset($parameters['code']);
			$collection = $cm->getCollection($code, $parameters);
			if ($collection !== null)
			{
				$event->setResult($this->generateResult($collection));
			}
			else
			{
				throw new \RuntimeException('Collection "' .$code .'" not found', 999999);
			}
		}
		else
		{
			throw new \RuntimeException('Parameter "code" is required', 999999);
		}
	}

	/**
	 * @param \Change\Collection\CollectionInterface $collection
	 * @return \Change\Http\Rest\V1\ArrayResult
	 */
	protected function generateResult($collection)
	{
		$array = array('code' => $collection->getCode(), 'items' => array());
		$result = new \Change\Http\Rest\V1\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		foreach ($collection->getItems() as $item)
		{
			$array['items'][$item->getValue()] = array('label' => $item->getLabel(), 'title' => $item->getTitle());
		}

		$result->setArray($array);
		return $result;
	}
}
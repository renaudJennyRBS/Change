<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

use Change\Http\BaseResolver;
use Change\Http\Event;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\V1\Resolver
 */
class Resolver extends BaseResolver
{
	/**
	 * Set Event params.
	 * Event Params Initialized:
	 *  - website, websiteUrlManager
	 *  - section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - pagination
	 *  - data
	 * @param Event $event
	 * @return void
	 */
	public function resolve($event)
	{
		/** @var $request \Change\Http\Ajax\Request */
		$request = $event->getRequest();
		$applicationServices = $event->getApplicationServices();

		$array = $request->getJSON();
		if ($array === false)
		{
			$exception = new \RuntimeException('Bad JSON request', 999999);
			$exception->httpStatusCode = \Zend\Http\Response::STATUS_CODE_400;
			throw $exception;
		}
		$context = new Context($event->getApplication(), $applicationServices->getDocumentManager(), $array);
		$context->populateEventParams($event);
	}
}
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
 * @name \Change\Http\Rest\V1\Actions\RenderRichText
 */
class RenderRichText
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();

		$profile = $request->getQuery('profile');
		if (! $profile)
		{
			throw new \RuntimeException("Missing profile parameter: can be 'Admin', 'Website', ...", 999999);
		}

		if ($request->isPost())
		{
			$rawText = $request->getContent();
		}
		elseif ($request->isGet())
		{
			$rawText = $request->getQuery('rawText');
		}
		else
		{
			$result = $event->getController()->notAllowedError($request->getMethod(), array(Request::METHOD_GET, Request::METHOD_POST));
			$event->setResult($result);
			return;
		}

		$richText = new \Change\Documents\RichtextProperty();
		$richText->setRawText($rawText);
		$richText->setEditor($request->getQuery('editor', 'Markdown'));

		$applicationServices = $event->getApplicationServices();
		$event->setResult($this->generateResult($applicationServices->getRichTextManager()->render($richText, $profile)));
	}

	/**
	 * @param string $html
	 * @return \Change\Http\Rest\V1\ArrayResult
	 */
	protected function generateResult($html)
	{
		$result = new \Change\Http\Rest\V1\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setArray(array('html' => $html));
		return $result;
	}
}
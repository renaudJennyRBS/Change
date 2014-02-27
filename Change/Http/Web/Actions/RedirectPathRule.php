<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Actions;

use Change\Http\Result;
use Change\Http\Web\PathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\RedirectPathRule
 */
class RedirectPathRule
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getParam('pathRule');
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}

		$result = new Result();
		$result->setHttpStatusCode($pathRule->getHttpStatus());
		if ($pathRule->getLocation())
		{
			$result->setHeaderLocation($pathRule->getLocation());
		}
		else
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}
		$event->setResult($result);
	}
}

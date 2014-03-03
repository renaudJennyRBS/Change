<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\Logout
*/
class Logout extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 * @return mixed|void
	 */
	public function execute(Event $event)
	{
		$this->logout($event);
	}

	/**
	 * @param Event $event
	 */
	public function logout(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
			unset($session[$website->getId()]);
			$data = array();
		}
		else
		{
			$data = array('error' => 'Invalid website');
		}

		$result = new \Change\Http\Web\Result\AjaxResult($data);
		$event->setResult($result);
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Http\Web;

use Change\Http\Web\Event;

/**
* @name \Rbs\Generic\Http\Web\GetError
*/
class GetError extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$errId = $request->isPost() ? $request->getPost('errId',  $request->getQuery('errId')) : $request->getQuery('errId');
		if ($errId)
		{
			$session = new \Zend\Session\Container('Change_Errors');
			if (isset($session[$errId]) && is_array($session[$errId]))
			{
				$event->setResult($this->getNewAjaxResult($session[$errId]));
				return;
			}
		}
		$event->setResult($this->getNewAjaxResult(array('exception' => array())));
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Actions;

use Zend\Stdlib\Parameters;

/**
 * @name \Change\Http\Web\Actions\AbstractAjaxAction
 */
abstract class AbstractAjaxAction
{
	function __invoke()
	{
		if (func_num_args() === 1)
		{
			$event = func_get_arg(0);
			if ($event instanceof \Change\Http\Web\Event)
			{
				$this->decodePostJSON($event);
				$this->execute($event);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	abstract public function execute(\Change\Http\Web\Event $event);

	/**
	 * @param array $data
	 * @return \Change\Http\Web\Result\AjaxResult
	 */
	protected function getNewAjaxResult(array $data = array())
	{
		return new \Change\Http\Web\Result\AjaxResult($data);
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 */
	protected function decodePostJSON(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		if ($request->isPost())
		{
			try
			{
				$h = $request->getHeaders('Content-Type');
			}
			catch (\Exception $e)
			{
				//Header not found
				return;
			}

			if ($h && ($h instanceof \Zend\Http\Header\ContentType))
			{
				if (strpos($h->getFieldValue(), 'application/json') === 0)
				{
					$string = file_get_contents('php://input');

					$data = json_decode($string, true);
					if (JSON_ERROR_NONE === json_last_error())
					{
						if (is_array($data))
						{
							$request->setPost(new Parameters($data));
						}
					}
				}
			}
		}
	}
}
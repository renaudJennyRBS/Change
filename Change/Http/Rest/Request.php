<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest;

use Zend\Stdlib\Parameters;

/**
 * @name \Change\Http\Request\Rest
 */
class Request extends \Change\Http\Request
{
	public function __construct()
	{
		parent::__construct();
		if (in_array($this->getMethod(), array('PUT', 'POST')))
		{
			try
			{
				$h = $this->getHeaders('Content-Type');
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
							if (\Zend\Stdlib\ArrayUtils::isList($data))
							{
								$this->setPost(new Parameters(['data' => $data]));
							}
							else
							{
								$this->setPost(new Parameters($data));
							}
						}
					}
				}
			}
		}
	}
}
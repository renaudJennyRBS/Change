<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax;

/**
 * @name \Change\Http\Ajax\Request
 */
class Request extends \Change\Http\Request
{
	/**
	 * @var array|false
	 */
	protected $JSON = false;

	public function __construct()
	{
		parent::__construct();
		if (!$this->isPost())
		{
			return;
		}

		try
		{
			$h = $this->getHeaders('X-HTTP-Method-Override');
			if ($h) {
				$method = $h->getFieldValue();
				switch ($method)
				{
					case static::METHOD_GET:
					case static::METHOD_PUT:
					case static::METHOD_DELETE:
					case static::METHOD_POST:
						$this->setMethod($method);
						break;
					default:
						return;
				}
			}
		}
		catch (\Exception $e)
		{
			// Header not found.
			return;
		}

		$h = $this->getHeaders('Content-Type');
		if ($h instanceof \Zend\Http\Header\ContentType)
		{
			if (strpos($h->getFieldValue(), 'application/json') === 0)
			{
				$string = file_get_contents('php://input');
				$data = json_decode($string, true);
				if (JSON_ERROR_NONE === json_last_error())
				{
					$this->JSON = $data;
				}
			}
		}
	}

	/**
	 * @return array|boolean
	 */
	public function getJSON()
	{
		return $this->JSON;
	}

	/**
	 * @param array $JSON
	 * @return $this
	 */
	public function setJSON(array $JSON)
	{
		$this->JSON = $JSON;
		return $this;
	}
}
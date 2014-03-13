<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\AtosSips;

/**
 * @name \Rbs\Payment\AtosSips\Request
 * @method \Rbs\Payment\AtosSips\Request setReturnContext(string $returnContext)
 */
class Request
{

	/**
	 * @var string
	 */
	protected $binPathFile;
	/**
	 * @var array
	 */
	protected $params = [];

	/**
	 * @param string $merchantId
	 * @return $this
	 */
	public function setMerchantId($merchantId)
	{
		$this->params['merchant_id'] = strval($merchantId);
		return $this;
	}

	/**
	 * @param string $merchantCountry
	 * @return $this
	 */
	public function setMerchantCountry($merchantCountry)
	{
		$this->params['merchant_country'] = strval($merchantCountry);
		return $this;
	}

	/**
	 * @param string $amount
	 * @return $this
	 */
	public function setAmount($amount)
	{
		$this->params['amount'] = strval($amount);
		return $this;
	}

	/**
	 * @param string $currencyCode
	 * @return $this
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->params['currency_code'] = strval($currencyCode);
		return $this;
	}

	/**
	 * @param string $transactionId
	 * @return $this
	 */
	public function setTransactionId($transactionId)
	{
		$this->params['transaction_id'] = strval($transactionId);
		return $this;
	}

	/**
	 * @param string $normalReturnUrl
	 * @return $this
	 */
	public function setNormalReturnUrl($normalReturnUrl)
	{
		$this->params['normal_return_url'] = strval($normalReturnUrl);
		return $this;
	}

	/**
	 * @param string $cancelReturnUrl
	 * @return $this
	 */
	public function setCancelReturnUrl($cancelReturnUrl)
	{
		$this->params['cancel_return_url'] = strval($cancelReturnUrl);
		return $this;
	}

	/**
	 * @param string $automaticResponseUrl
	 * @return $this
	 */
	public function setAutomaticResponseUrl($automaticResponseUrl)
	{
		$this->params['automatic_response_url'] = strval($automaticResponseUrl);
		return $this;
	}


	/**
	 * @param string $pathfile
	 * @return $this
	 */
	public function setPathfile($pathfile)
	{
		$this->params['pathfile'] = strval($pathfile);
		return $this;
	}

	/**
	 * @param string $binPathFile
	 * @return $this
	 */
	public function setBinPathFile($binPathFile)
	{
		$this->binPathFile = strval($binPathFile);
		return $this;
	}

	protected function buildEscapedParams()
	{
		$escParams = [];
		foreach ($this->params as $name => $value)
		{
			if ($name !== 'data')
			{
				$value = escapeshellcmd($value);
			}
			$escParams[] = $name . '=' . $value;
		}
		return implode(' ', $escParams);
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return array
	 */
	public function encodeRequest()
	{
		if (file_exists($this->binPathFile))
		{
			$cmd = $this->binPathFile . ' ' . $this->buildEscapedParams();
			$result = exec($cmd);
			if (is_string($result))
			{
				$parts = explode('!', $result);
				if (count($parts) !== 5)
				{
					$parts = ['-1', 'Unexpected Result', $result];
				}
				else
				{
					$parts = array_slice($parts, 1, 3);
				}
			}
			else
			{
				$parts = ['-1', 'Unable to exec', $this->binPathFile];
			}
		}
		else
		{
			$parts = ['-1', 'file not found', $this->binPathFile];
		}

		return $parts;
	}

	/**
	 * @param string $name
	 * @param string|null $arguments
	 * @return $this
	 */
	public function __call($name, $arguments)
	{
		if (strlen($name) > 3 && substr($name, 0, 3) === 'set' && count($arguments) === 1)
		{
			$pName = substr(preg_replace_callback('/[A-Z]/', function ($matches)
			{
				return '_' . strtolower($matches[0]);
			}, ucfirst(substr($name, 3))), 1);
			if ($arguments[0] === null)
			{
				unset($this->params[$pName]);
			}
			else
			{
				$this->params[$pName] = strval($arguments[0]);
			}
			return $this;
		}
		return $this;
	}
}
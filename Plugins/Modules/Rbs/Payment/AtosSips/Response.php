<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\AtosSips;

/**
 * @name \Rbs\Payment\AtosSips\Response
 */
class Response
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

	/**
	 * @param string $message
	 * @return $this
	 */
	public function setMessage($message)
	{
		$this->params['message'] = strval($message);
		return $this;
	}

	protected function buildEscapedParams()
	{
		$escParams = [];
		foreach ($this->params as $name => $value)
		{
			$escParams[] = $name . '=' . escapeshellcmd($value);
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

	public function decode()
	{
		if (file_exists($this->binPathFile))
		{
			$cmd = $this->binPathFile . ' ' . $this->buildEscapedParams();
			$result = exec($cmd);
			if (is_string($result))
			{
				$parts = explode('!', $result);
				if (count($parts) !== 45)
				{
					$parts = ['code' => '-1', 'error' =>'Unexpected Result: ' .$result];
				}
				else
				{
					$values = array_map(function($v) {return $v === '' ? null : $v;}, array_slice($parts, 1, 43));
					$keys = ['code', 'error', 'merchant_id', 'merchant_country', 'amount', 'transaction_id', 'payment_means',
						'transmission_date', 'payment_time', 'payment_date', 'response_code', 'payment_certificate', 'authorisation_id', 'currency_code',
						'card_number', 'cvv_flag', 'cvv_response_code', 'bank_response_code', 'complementary_code', 'complementary_info',
						'return_context', 'caddie', 'receipt_complement', 'merchant_language', 'language', 'customer_id', 'order_id',
						'customer_email', 'customer_ip_address', 'capture_day', 'capture_mode', 'data', 'order_validity',
						'transaction_condition', 'statement_reference', 'card_validity', 'score_value',
						'score_color', 'score_info', 'score_threshold', 'score_profile', 'threed_ls_code', 'threed_relegation_code'];
					$parts = array_combine($keys, $values);
				}
			}
			else
			{
				$parts = ['code' => '-1', 'error' => 'Unable to exec: '. $this->binPathFile];
			}
		}
		else
		{
			$parts = ['code' => '-1', 'error' => 'file not found: ' . $this->binPathFile];
		}
		return $parts;
	}
}
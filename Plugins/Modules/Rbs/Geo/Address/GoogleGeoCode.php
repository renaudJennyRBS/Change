<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\GoogleGeoCode
 */
class GoogleGeoCode
{
	/**
	 * @var \Rbs\Geo\GeoManager
	 */
	protected $geoManager;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @var string
	 */
	protected $key;


	public function __construct(\Rbs\Geo\GeoManager $geoManager)
	{
		$this->geoManager = $geoManager;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 * @return $this
	 */
	public function setLogging(\Change\Logging\Logging $logging = null)
	{
		$this->logging = $logging;
		return $this;
	}

	/**
	 * @param string $key
	 * @return $this
	 */
	public function setKey($key)
	{
		$this->key = $key;
		return $this;
	}

	public function getCoordinates(\Rbs\Geo\Address\AddressInterface $address)
	{
		$lines = $address->getLines();
		if ($lines > 2)
		{
			$lines[0] = '';
			$search = trim(implode(' ', $lines));
			$url = 'https://maps.googleapis.com/maps/api/geocode/json?key='.urlencode($this->key).'&address=' . urlencode($search);
			$result = $this->getUrlContent($url);

			if (is_string($result))
			{
				$data = json_decode($result, true);
				if (is_array($data) && isset($data['results']) && is_array($data['results']) && count($data['results']) == 1)
				{
					$data = $data['results'][0];
					if (isset($data['geometry']['location']))
					{
						$data['latitude'] = floatval($data['geometry']['location']['lat']);
						$data['longitude'] = floatval($data['geometry']['location']['lng']);
						$data['formattedAddress'] = isset($data['formatted_address']) ? $data['formatted_address'] : null;
						return $data;
					}
					elseif ($this->logging)
					{
						$this->logging->error('Invalid result: ' . $result);
					}
				}
				elseif ($this->logging)
				{
					$this->logging->error(json_last_error() . ' ' . json_last_error_msg());
				}
			}
		}
		return null;
	}

	/**
	 * @param string $url
	 * @return bool|string
	 */
	protected function getUrlContent($url)
	{
		if ($this->logging)
		{
			$this->logging->info($url);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_errno($ch) . ' ' . curl_error($ch);
		curl_close($ch);
		if ($httpCode >= 200 && $httpCode < 300)
		{
			return $data;
		}
		elseif ($this->logging)
		{
			$this->logging->error($error);
		}
		return false;
	}
}
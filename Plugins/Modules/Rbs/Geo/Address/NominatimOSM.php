<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\NominatimOSM
 */
class NominatimOSM
{
	/**
	 * @var \Rbs\Geo\GeoManager
	 */
	protected $geoManager;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

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

	public function getCoordinates(\Rbs\Geo\Address\AddressInterface $address)
	{
		$lines = $address->getLines();
		if ($lines > 2)
		{
			$lines[0] = '';
			$search = trim(implode(' ', $lines));
			$url = 'http://nominatim.openstreetmap.org/search?format=json&addressdetails=1&countrycodes='.
				strtolower($address->getCountryCode()) .'&q=' . urlencode($search);
			$result = $this->getUrlContent($url);

			if (is_string($result))
			{
				$data = json_decode($result, true);
				if (is_array($data))
				{
					if (isset($data[0]) && is_array($data[0]))
					{
						$data = $data[0];
					}

					if (isset($data['lat']) && isset($data['lon']))
					{
						$data['latitude'] = floatval($data['lat']);
						$data['longitude'] = floatval($data['lon']);
						$data['formattedAddress'] = null;
						if (isset($data['address']) && is_array($data['address']))
						{
							$a = $data['address'];

							$formattedAddress = isset($a['house_number']) ? $a['house_number'] : '';

							if (isset($a['road'])) {
								$formattedAddress .= ($formattedAddress ? ' ' : '') . $a['road'];
							}
							if ($formattedAddress && (isset($a['postcode']) || isset($a['city']))) {
								$formattedAddress .= ',';
							}

							if (isset($a['postcode'])) {
								$codes = explode(';', $a['postcode']);
								$formattedAddress .= ($formattedAddress ? ' ' : '') . $codes[0];
							}

							if (isset($a['city'])) {
								$formattedAddress .= ($formattedAddress ? ' ' : '') . $a['city'];
							}

							if (isset($a['country'])) {
								$formattedAddress .= ($formattedAddress ? ', ' : '') . $a['country'];
							}

							if ($formattedAddress) {
								$data['formattedAddress'] = $formattedAddress;
							}
						}
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
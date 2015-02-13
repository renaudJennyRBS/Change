<?php
namespace Rbs\Storelocator\Documents;

/**
 * @name \Rbs\Storelocator\Documents\Store
 */
class Store extends \Compilation\Rbs\Storelocator\Documents\Store
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		if ($this !== $event->getDocument())
		{
			return;
		}

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$address = $this->getAddress();
			$restResult->setProperty('address', $address ? $address->toArray() : null);
			$restResult->setProperty('coordinates', $this->getCoordinates());

			$openingHours = $this->addDayTitle($this->getOpeningHours(), $event->getApplicationServices()->getI18nManager());
			$restResult->setProperty('openingHours', $openingHours);
			$restResult->setProperty('specialDays', $this->getSpecialDays());
			$card = $this->getCard();
			$restResult->setProperty('card', $card ? $card : null);
		}
	}

	/**
	 * @param array $openingHours
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return array
	 */
	public function addDayTitle(array $openingHours, \Change\I18n\I18nManager $i18nManager)
	{
		$date = new \DateTime('2014-12-07');
		$LCID = $i18nManager->getLCID();
		$oneDay = new \DateInterval('P1D');
		foreach ($openingHours as $i => $day)
		{
			$openingHours[$i]['title'] =  ucfirst($i18nManager->formatDate($LCID, $date, 'cccc'));
			$openingHours[$i]['shortTitle'] =  strtoupper($i18nManager->formatDate($LCID, $date, 'ccc'));
			$date->add($oneDay);
		}
		usort($openingHours, function($a, $b) { return (($a['num'] + 6) % 7) < (($b['num'] + 6) % 7) ? -1 : 1;});
		return $openingHours;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return bool
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch ($name) {
			case 'address':
				$genericServices = $event->getServices('genericServices');
				if ($genericServices instanceof \Rbs\Generic\GenericServices)
				{
					$value = $genericServices->getGeoManager()->validateAddress($value);
				}
				$this->setAddress($value);
				return true;
			case 'coordinates':
				$this->setCoordinates($value);
				return true;
			case 'openingHours':
				$this->setOpeningHours($value);
				return true;
			case 'specialDays':
				$this->setSpecialDays($value);
				return true;
			case 'card':
				$this->setCard($value);
				return true;
		}
		return parent::processRestData($name, $value, $event);
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress|\Rbs\Geo\Address\AddressInterface|array|null $addressData
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	protected function buildAddress($addressData)
	{
		if ($addressData instanceof \Rbs\Geo\Address\BaseAddress)
		{
			return $addressData;
		}
		elseif ($addressData instanceof \Rbs\Geo\Address\AddressInterface)
		{
			return new \Rbs\Geo\Address\BaseAddress($addressData);
		}
		elseif (is_array($addressData) && isset($addressData['fields']) && isset($addressData['common']))
		{
			return new \Rbs\Geo\Address\BaseAddress($addressData);
		}
		return null;
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	public function getAddress()
	{
		return $this->buildAddress($this->getAddressData());
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress|\Rbs\Geo\Address\AddressInterface|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		if ($address instanceof \Rbs\Geo\Address\AddressInterface)
		{
			$this->setAddressData($address->toArray());
		}
		else
		{
			$address = $this->buildAddress($address);
			$this->setAddressData($address ? $address->toArray() : null);
		}
		return $this;
	}

	/**
	 * @param array $coordinatesData
	 * @return array|null
	 */
	protected function buildCoordinates($coordinatesData)
	{
		if (is_array($coordinatesData) && isset($coordinatesData['latitude']) && isset($coordinatesData['longitude']))
		{
			if (is_numeric($coordinatesData['latitude']) && is_numeric($coordinatesData['longitude']))
			{
				return ['latitude' => floatval($coordinatesData['latitude']), 'longitude' => floatval($coordinatesData['longitude'])];
			}
		}
		return null;
	}

	/**
	 * @param array $coordinates
	 * @return $this
	 */
	public function setCoordinates($coordinates)
	{
		$coordinates = $this->buildCoordinates($coordinates);
		$geoData = $this->getGeoData();
		if (is_array($geoData))
		{
			$this->setGeoData(array_merge($geoData, ['coordinates' => $coordinates]));
		}
		else
		{
			$this->setGeoData(['coordinates' => $coordinates]);
		}
		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getCoordinates()
	{
		$geoData = $this->getGeoData();
		if (is_array($geoData) && isset($geoData['coordinates']))
		{
			return $geoData['coordinates'];
		}
		return null;
	}

	/**
	 * @return array
	 */
	protected function buildDefaultOpeningHours()
	{
		$openingHours = [];
		for ($num = 0; $num < 7; $num++)
		{
			$day = ['num' => $num, 'viewPos' => (($num + 6) % 7),
				'amBegin' => null, 'amEnd' => null, 'pmBegin' => null, 'pmEnd' => null];
			$openingHours[] = $day;
		}
		return $openingHours;
	}

	/**
	 * @return array
	 */
	public function getOpeningHours()
	{
		$data = $this->getOpeningHoursData();
		if (is_array($data) && isset($data['openingHours'])) {
			return $data['openingHours'];
		}
		return $this->buildDefaultOpeningHours();
	}

	/**
	 * @param array $openingHours
	 * @return $this
	 */
	public function setOpeningHours($openingHours)
	{
		$openingHoursData = [];
		if (is_array($openingHours) && count($openingHours) == 7)
		{
			for ($num = 0; $num < 7; $num++)
			{
				foreach ($openingHours as $day)
				{
					if (is_array($day) && isset($day['num']) && $day['num'] == $num)
					{
						$day += ['viewPos' => (($num + 6) % 7), 'amBegin' => null, 'amEnd' => null, 'pmBegin' => null, 'pmEnd' => null];
						foreach (['amBegin', 'amEnd', 'pmBegin', 'pmEnd'] as $key)
						{
							$v = $day[$key];
							if (is_string($v))
							{
								$vp = explode(':', $v);
								if (count($vp) == 2 && is_numeric($vp[0]) && is_numeric($vp[1]))
								{
									if ($vp[0] >= 0 && $vp[0] < 24 && $vp[1] >= 0 && $vp[1] < 60) {
										$day[$key] = str_pad(strval(intval($vp[0])), 2, '0', STR_PAD_LEFT) . ':' .
											str_pad(strval(intval($vp[1])), 2, '0', STR_PAD_LEFT);
										continue;
									}
								}
							}
							$day[$key] = null;
						}
						$openingHoursData[] = $day;
						break;
					}
				}
			}
		}

		$data = $this->getOpeningHoursData();
		if (count($openingHoursData) == 7)
		{
			if ( is_array($data))
			{
				$data['openingHours'] = $openingHoursData;
			}
			else
			{
				$data = ['openingHours' =>  $openingHoursData];
			}
			$this->setOpeningHoursData($data);
		}
		elseif (is_array($data) && isset($data['openingHours']))
		{
			unset($data['openingHours']);
			$this->setOpeningHoursData($data);
		}
		return $this;
	}


	/**
	 * @return array
	 */
	public function getSpecialDays()
	{
		$data = $this->getOpeningHoursData();
		if (is_array($data) && isset($data['specialDays']))
		{
			return $data['specialDays'];
		}
		return [];
	}

	/**
	 * @param array $specialDays
	 * @return $this
	 */
	public function setSpecialDays($specialDays)
	{
		$specialDaysData = [];
		if (is_array($specialDays))
		{
			foreach ($specialDays as $day)
			{
				if (is_array($day) && isset($day['date']))
				{
					$date = (new \DateTime($day['date']))->add(new \DateInterval('PT12H'));
					$day['date'] = $date->format('Y-m-d') . 'T00:00:00+0000';
					$day += ['amBegin' => null, 'amEnd' => null, 'pmBegin' => null, 'pmEnd' => null];
					foreach (['amBegin', 'amEnd', 'pmBegin', 'pmEnd'] as $key)
					{
						$v = $day[$key];
						if (is_string($v))
						{
							$vp = explode(':', $v);
							if (count($vp) == 2 && is_numeric($vp[0]) && is_numeric($vp[1]))
							{
								if ($vp[0] >= 0 && $vp[0] < 24 && $vp[1] >= 0 && $vp[1] < 60) {
									$day[$key] = str_pad(strval(intval($vp[0])), 2, '0', STR_PAD_LEFT) . ':' .
										str_pad(strval(intval($vp[1])), 2, '0', STR_PAD_LEFT);
									continue;
								}
							}
						}
						$day[$key] = null;
					}
					$specialDaysData[$day['date']] = $day;
				}
			}
		}

		$data = $this->getOpeningHoursData();
		if (count($specialDaysData))
		{
			ksort($specialDaysData);
			if (is_array($data))
			{
				$data['specialDays'] = array_values($specialDaysData);
			}
			else
			{
				$data = ['specialDays' =>  array_values($specialDaysData)];
			}
			$this->setOpeningHoursData($data);
		}
		elseif (is_array($data) && isset($data['specialDays']))
		{
			unset($data['specialDays']);
			$this->setOpeningHoursData($data);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCard()
	{
		$data = $this->getCardData();
		if (is_array($data) && count($data))
		{
			return $data;
		}
		return [];
	}

	/**
	 * @param array $card
	 * @return $this
	 */
	public function setCard($card)
	{
		if (is_array($card) && count($card))
		{
			foreach ($card as $key => $value)
			{
				if ($value === null || $value === '' || (is_array($value) && !count($value)))
				{
					unset($card[$key]);
				}
			}
			$this->setCardData($card ? $card : null);
		}
		else
		{
			$this->setCardData(null);
		}
		return $this;
	}
}

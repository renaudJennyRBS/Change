<?php
namespace Rbs\Price\Documents;

/**
 * @name \Rbs\Price\Documents\Tax
 */
class Tax extends \Compilation\Rbs\Price\Documents\Tax
{
	const CATEGORIES_KEY = 'c';
	const ZONE_KEY = 'z';
	const RATES_KEY = 'r';

	/**
	 * @var float[][]
	 */
	protected $ratesCache = array();

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone)
	{
		if (!isset($this->ratesCache[$category][$zone]))
		{
			$data = $this->getData();
			$categoryIndex = array_search($category, $data[self::CATEGORIES_KEY]);
			if ($categoryIndex === false)
			{
				$categoryIndex = 0;
			}
			$zoneIndex = array_search($zone, $data[self::ZONE_KEY]);
			if ($zoneIndex === false)
			{
				$zoneIndex = array_search($this->getDefaultZone(), $data[self::ZONE_KEY]);
				if ($zoneIndex === false)
				{
					$zoneIndex = 0;
				}
			}
			$this->ratesCache[$category][$zone] = isset($data[self::RATES_KEY][$categoryIndex][$zoneIndex]) ? floatval($data[self::RATES_KEY][$categoryIndex][$zoneIndex]) : 0;
		}
		return $this->ratesCache[$category][$zone];
	}

	/**
	 * @return string | null
	 */
	public function getDefaultZone()
	{
		$data = $this->getData();
		return isset($data[self::ZONE_KEY]) && is_array($data[self::ZONE_KEY]) && count($data[self::ZONE_KEY])  ? $data[self::ZONE_KEY][0] : null;
	}

	/**
	 * @param string $defaultZone
	 * @return $this
	 */
	public function setDefaultZone($defaultZone)
	{
		return $this;
	}
}
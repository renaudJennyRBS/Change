<?php
namespace Rbs\Price\Documents;

/**
 * @name \Rbs\Price\Documents\Tax
 */
class Tax extends \Compilation\Rbs\Price\Documents\Tax implements \Rbs\Price\Tax\TaxInterface
{
	const CATEGORIES_KEY = 'c';
	const ZONES_KEY = 'z';
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
		if ($category == null || $zone == null)
		{
			return 0.0;
		}
		if (!isset($this->ratesCache[$category][$zone]))
		{
			$data = $this->getData();
			$categoryIndex = array_search($category, $data[self::CATEGORIES_KEY]);
			$zoneIndex = array_search($zone, $data[self::ZONES_KEY]);
			if ($categoryIndex === false || $zoneIndex === false)
			{
				return 0.0;
			}
			$this->ratesCache[$category][$zone] = isset($data[self::RATES_KEY][$categoryIndex][$zoneIndex]) ?
				0.01 * floatval($data[self::RATES_KEY][$categoryIndex][$zoneIndex]) : 0.0;
		}
		return $this->ratesCache[$category][$zone];
	}

	/**
	 * @return string[]
	 */
	public function getCategoryCodes()
	{
		$data = $this->getData();
		return isset($data[self::CATEGORIES_KEY]) && is_array($data[self::CATEGORIES_KEY]) ? $data[self::CATEGORIES_KEY] : array();
	}

	/**
	 * @return string[]
	 */
	public function getZoneCodes()
	{
		$data = $this->getData();
		return isset($data[self::ZONES_KEY]) && is_array($data[self::ZONES_KEY]) ? $data[self::ZONES_KEY] : array();
	}

	/**
	 * @return string | null
	 */
	public function getDefaultZone()
	{
		$data = $this->getData();
		return isset($data[self::ZONES_KEY]) && is_array($data[self::ZONES_KEY])
		&& count($data[self::ZONES_KEY]) ? $data[self::ZONES_KEY][0] : null;
	}

	/**
	 * @param string $defaultZone
	 * @return $this
	 */
	public function setDefaultZone($defaultZone)
	{
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = ['id' => $this->getId(), 'code' => $this->getCode(), 'rounding' => $this->getRounding(), 'cascading' => $this->getCascading(), 'rates'=> []];
		$data = $this->getData();
		if (isset($data[self::RATES_KEY]))
		{
			foreach ($data[self::RATES_KEY] as $ci => $zir)
			{
				foreach($zir as $zi => $rate)
				{
					$zone = (isset($data[self::ZONES_KEY][$zi])) ? $data[self::ZONES_KEY][$zi] : null;
					$category  = (isset($data[self::CATEGORIES_KEY][$ci])) ? $data[self::CATEGORIES_KEY][$ci] : null;
					if ($zone && $category)
					{
						$array['rates'][$category][$zone] = 0.01 * floatval($rate);
					}
				}
			}
		}
		return $array;
	}
}
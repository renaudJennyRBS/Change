<?php
namespace Rbs\Generic\Json;

use Change\Documents\Property;

/**
 * @name \Rbs\Generic\Json\JsonConverter
 */
class JsonConverter
{
	/**
	 * @api
	 * @param mixed $propertyValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @return array|null|string
	 */
	public function toRestValue($propertyValue, $type)
	{
		$restValue = null;
		switch ($type)
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if ($propertyValue instanceof \DateTime)
				{
					$propertyValue->setTimezone(new \DateTimeZone('UTC'));
					$restValue = $propertyValue->format(\DateTime::ISO8601);
				}
				break;
			case Property::TYPE_RICHTEXT:
				if ($propertyValue instanceof \Change\Documents\RichtextProperty)
				{
					$restValue = $propertyValue->toArray();
				}
				break;
			case Property::TYPE_STORAGEURI:
				if (is_string($propertyValue))
				{
					$restValue = array('storageURI' => $propertyValue);
				}
				break;
			default:
				$restValue = $propertyValue;
				break;
		}
		return $restValue;
	}

	/**
	 * @api
	 * @param mixed $restValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @return array|\Change\Documents\AbstractDocument|\DateTime|null|string
	 */
	public function toPropertyValue($restValue, $type)
	{
		$value = null;
		switch ($type)
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if (is_string($restValue))
				{
					$value = new \DateTime($restValue);
					if ($value === false)
					{
						$value = null;
					}
				}
				break;
			case Property::TYPE_RICHTEXT:
				$value = new \Change\Documents\RichtextProperty($restValue);
				break;
			case Property::TYPE_STORAGEURI:
				if (is_array($restValue) && isset($restValue['storageURI']))
				{
					$value = $restValue['storageURI'];
				}
				elseif (is_string($restValue))
				{
					$value = $restValue;
				}
				break;
			default:
				$value = $restValue;
				break;
		}
		return $value;
	}
} 
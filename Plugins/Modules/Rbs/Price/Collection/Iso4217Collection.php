<?php

namespace Rbs\Price\Collection;

use Zend\Json\Json;

/**
 * @name \Rbs\Price\Collection\Iso4217Collection
 */
class Iso4217Collection implements \Change\Collection\CollectionInterface
{
	protected static $currencies = null;

	public function __construct()
	{
		if (static::$currencies === null)
		{
			static::$currencies = array();
			foreach(Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'currencies.json'), Json::TYPE_ARRAY) as $code => $value)
			{
				if (!\Change\Stdlib\String::isEmpty($code))
				{
					static::$currencies[$code] = new IsoCurrency($value['label'], $code);
				}
			}
			ksort(static::$currencies);
		}
	}

	/**
	 * @return \Change\Collection\ItemInterface[]
	 */
	public function getItems()
	{
		return static::$currencies;
	}

	/**
	 * @param mixed $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value)
	{
		return isset(static::$currencies[$value]) ? static::$currencies[$value] : null;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return "Rbs_Price_Collection_Iso4217";
	}
}
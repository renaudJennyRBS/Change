<?php
namespace Change\Stdlib;

/**
 * @name \Change\Stdlib\String
 */
class String
{
	/**
	 * UTF8-safe string length.
	 * @api
	 * @param string $string
	 * @return integer
	 */
	public static function length($string)
	{
		return mb_strlen($string, "UTF-8");
	}
	
	/**
	 * UTF8-safe strtolower.
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function toLower($string)
	{
		return mb_strtolower($string, "UTF-8");
	}
	
	/**
	 * UTF8-safe strtoupper.
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function toUpper($string)
	{
		return mb_strtoupper($string, "UTF-8");
	}
	
	/**
	 * UTF8-safe ucfirst.
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function ucfirst($string)
	{
		return self::toUpper(self::subString($string, 0, 1)) . self::subString($string, 1);
	}
	
	/**
	 * UTF8-safe Sub string.
	 * @api
	 * @param string $string
	 * @param integer $start
	 * @param integer $length
	 * @return string
	 */
	public static function subString($string, $start, $length = null)
	{
		if (is_null($length))
		{
			$length = self::length($string);
		}
		return mb_substr($string, $start, $length, "UTF-8");
	}
	
	/**
	 * @api
	 * @param string $string
	 * @param integer $maxLen
	 * @param string $dots
	 * @return string
	 */
	public static function shorten($string, $maxLen = 255, $dots = '...')
	{
		if (self::length($string) > $maxLen)
		{
			$string = self::subString($string, 0, $maxLen - self::length($dots)) . $dots;
		}
		return $string;
	}
}
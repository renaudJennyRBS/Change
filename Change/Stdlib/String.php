<?php
namespace Change\Stdlib;

/**
 * @api
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
		return mb_strlen($string, 'UTF-8');
	}

	/**
	 * UTF8-safe strtolower.
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function toLower($string)
	{
		return mb_strtolower($string, 'UTF-8');
	}

	/**
	 * UTF8-safe strtoupper.
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function toUpper($string)
	{
		return mb_strtoupper($string, 'UTF-8');
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
		return mb_substr($string, $start, $length, 'UTF-8');
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

	/**
	 * Generates an easily memorable string random string (for random password generation for example).
	 * @api
	 * @param int $length
	 * @param boolean $caseSensitive
	 * @return string
	 */
	public static function random($length = 8, $caseSensitive = true)
	{
		$randomString = '';
		$consonants = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'z', 'bl', 'br',
			'cl', 'cr', 'ch', 'dr', 'fl', 'fr', 'gl', 'gr', 'pl', 'pr', 'qu', 'sl', 'sr');
		$vowels = array('a', 'e', 'i', 'o', 'u', 'ae', 'ai', 'au', 'eu', 'ia', 'io', 'iu', 'oa', 'oi', 'ou', 'ua', 'ue', 'ui');

		if ($caseSensitive)
		{
			// Add upper consonant to consonants' array
			foreach ($consonants as $consonant)
			{
				$consonants[] = strtoupper($consonant);
			}
			// Add upper vowel to vowels' array
			foreach ($vowels as $vowel)
			{
				$vowels[] = strtoupper($vowel);
			}
		}
		$nbC = count($consonants) - 1;
		$nbV = count($vowels) - 1;

		for ($i = 0; $i < $length; $i++)
		{
			$randomString .= $consonants[rand(0, $nbC)] . $vowels[rand(0, $nbV)];
		}

		return substr($randomString, 0, $length);
	}

	/**
	 * @api
	 * @param string $string
	 * @return boolean
	 */
	public static function isEmpty($string)
	{
		return $string === null || (!is_array($string) && strlen(trim($string))) == 0;
	}

	/**
	 * @api
	 * @param string $haystack
	 * @param string $needle
	 * @param boolean $caseSensitive
	 * @return boolean
	 */
	public static function endsWith($haystack, $needle, $caseSensitive = true)
	{
		$len = self::length($needle);
		if ($caseSensitive)
		{
			return self::subString($haystack, -$len, $len) == $needle;
		}
		return self::toLower(self::subString($haystack, -$len, $len)) === self::toLower($needle);
	}

	/**
	 * @api
	 * @param string $haystack
	 * @param string $needle
	 * @param boolean $caseSensitive
	 * @return boolean
	 */
	public static function beginsWith($haystack, $needle, $caseSensitive = true)
	{
		$len = self::length($needle);
		if ($caseSensitive)
		{
			return self::subString($haystack, 0, $len) == $needle;
		}
		return self::toLower(self::subString($haystack, 0, $len)) === self::toLower($needle);
	}
}
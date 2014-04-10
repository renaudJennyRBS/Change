<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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

	protected static $fromAccents = array('à', 'â', 'ä', 'á', 'ã', 'å', 'À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'æ', 'Æ', 'ç', 'Ç', 'è', 'ê', 'ë', 'é', 'È', 'Ê',
		'Ë', 'É', 'ð', 'Ð', 'ì', 'î', 'ï', 'í', 'Ì', 'Î', 'Ï', 'Í', 'ñ', 'Ñ', 'ò', 'ô', 'ö', 'ó', 'õ', 'ø', 'Ò', 'Ô', 'Ö', 'Ó', 'Õ', 'Ø', 'œ', 'Œ',
		'ù', 'û', 'ü', 'ú', 'Ù', 'Û', 'Ü', 'Ú', 'ý', 'ÿ', 'Ý', 'Ÿ');
	protected static $toAccents = array('a', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'A', 'ae', 'AE', 'c', 'C', 'e', 'e', 'e', 'e', 'E', 'E',
		'E', 'E', 'ed', 'ED', 'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'n', 'N', 'o', 'o', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'O', 'O', 'oe', 'OE',
		'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'Y');

	/**
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function stripAccents($string)
	{
		return str_replace(static::$fromAccents, static::$toAccents, $string);
	}

	const DEFAULT_SUBSTITUTION_REGEXP = '/\{([a-z][A-Za-z0-9.]*)\}/';

	/**
	 * @param string $string
	 * @param array $substitutions
	 * @param string $regExp
	 * @return string|null
	 */
	public static function getSubstitutedString($string, $substitutions, $regExp = self::DEFAULT_SUBSTITUTION_REGEXP)
	{
		if (is_string($string) && $string)
		{
			if (count($substitutions))
			{
				$string = preg_replace_callback($regExp, function ($matches) use ($substitutions)
				{
					if (array_key_exists($matches[1], $substitutions))
					{
						return $substitutions[$matches[1]];
					}
					return '';
				}, $string);
			}
			return $string;
		}
		return null;
	}

	/**
	 * @api
	 * @param string $plainText
	 * @return string
	 */
	public static function htmlEscape($plainText)
	{
		return nl2br(htmlspecialchars($plainText, ENT_NOQUOTES, 'UTF-8'));
	}

	/**
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function attrEscape($string)
	{
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * @api
	 * @param string $string
	 * @param string $separator
	 * @return string
	 */
	public static function snakeCase($string, $separator = '_')
	{
		if (is_string($string) && is_string($separator))
		{
			$string = preg_replace('/([a-z0-9])([A-Z])/', '$1' . $separator . '$2', $string);
			$string = preg_replace('/[^a-z0-9]/', $separator, strtolower($string));

			// Remove consecutive occurrences of the separator.
			do
			{
				$oldString = $string;
				$string = str_replace($separator.$separator, $separator, $string);
			}
			while ($oldString !== $string);

			// Remove separator from the beginning and end of the string.
			$string = self::beginsWith($string, $separator) ? substr($string, self::length($separator)) : $string;
			$string = self::endsWith($string, $separator) ? substr($string, 0, - self::length($separator)) : $string;

			return $string;
		}
		return $string;
	}

	/**
	 * @api
	 * @param string $string
	 * @return string
	 */
	public static function camelCase($string)
	{
		if (is_string($string))
		{
			$string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);
			$string = implode('', array_map('ucfirst', explode(' ', $string)));
			return $string;
		}
		return $string;
	}
}
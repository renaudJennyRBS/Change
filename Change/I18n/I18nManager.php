<?php
namespace Change\I18n;

/**
 * @name \Change\I18n\I18nManager
 */
class I18nManager
{
	const SYNCHRO_MODIFIED = 'MODIFIED';
	const SYNCHRO_VALID = 'VALID';
	const SYNCHRO_SYNCHRONIZED = 'SYNCHRONIZED';

	/**
	 * @var array
	 */
	protected $langMap = null;

	/**
	 * @var array
	 */
	protected $ignoreTransform;

	/**
	 * @var array
	 */
	protected $transformers;

	/**
	 * @var string ex: "fr_FR"
	 */
	protected $uiLCID;

	/**
	 * @var string[] ex: "fr_FR"
	 */
	protected $supportedLCIDs = array();

	/**
	 * @var string
	 */
	protected $uiDateFormat;

	/**
	 * @var string
	 */
	protected $uiDateTimeFormat;

	/**
	 * @var string
	 */
	protected $uiTimeZone;
	
	/**
	 * @var array
	 */
	protected $i18nDocumentsSynchro = null;

	/**
	 * @var array
	 */
	protected $i18nKeysSynchro = null;

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	public function __construct()
	{
		$this->ignoreTransform = array('TEXT' => 'raw', 'HTML' => 'html');

		$this->transformers = array('lab' => 'transformLab', 'uc' => 'transformUc', 'ucf' => 'transformUcf', 'lc' => 'transformLc',
			'js' => 'transformJs', 'html' => 'transformHtml', 'text' => 'transformText', 'attr' => 'transformAttr', 'space' => 'transformSpace',
			'etc' => 'transformEtc', 'ucw' => 'transformUcw');
	}

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		$this->supportedLCIDs = $this->configuration->getEntry('i18n/supported-lcids', array('fr_FR'));
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 */
	public function setLogging(\Change\Logging\Logging $logging)
	{
		$this->logging = $logging;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * Get all supported LCIDs.
	 * @api
	 * @return string[] , ex: "fr_FR"
	 */
	public function getSupportedLCIDs()
	{
		return $this->supportedLCIDs;
	}

	/**
	 * Get the default LCID.
	 * @api
	 * @return string two lower-cased letters code, ex: "fr_FR"
	 */
	public function getDefaultLCID()
	{
		return $this->supportedLCIDs[0];
	}

	/**
	 * Get the UI LCID.
	 * @api
	 * @return string two lower-cased letters code, ex: "fr_FR"
	 */
	public function getLCID()
	{
		if ($this->uiLCID === null)
		{
			$this->setLCID($this->getDefaultLCID());
		}
		return $this->uiLCID;
	}

	/**
	 * Set the UI LCID.
	 * @api
	 * @throws \InvalidArgumentException if the lang is not supported
	 * @param string $LCID ex: "fr_FR"
	 */
	public function setLCID($LCID)
	{
		if (!in_array($LCID, $this->getSupportedLCIDs()))
		{
			throw new \InvalidArgumentException('Not supported language: ' . $LCID);
		}
		$this->uiLCID = $LCID;
	}

	/**
	 * Loads the i18n synchro configuration.
	 */
	protected function loadI18nSynchroConfiguration()
	{
		$data = $this->getConfiguration()->getEntry('i18n/synchro/documents', null);
		$this->i18nDocumentsSynchro = $this->cleanI18nSynchroConfiguration($data);
		$data = $this->getConfiguration()->getEntry('i18n/synchro/keys', null);
		$this->i18nKeysSynchro = $this->cleanI18nSynchroConfiguration($data);
	}

	/**
	 * Clean i18n synchro configuration.
	 * @see loadI18nSynchroConfiguration()
	 * @param array $data
	 */
	protected function cleanI18nSynchroConfiguration($data)
	{
		$result = array();
		if (is_array($data) && count($data))
		{
			$LCIDs = $this->getSupportedLCIDs();
			foreach ($data as $LCID => $froms)
			{
				if (in_array($LCID, $LCIDs))
				{
					$fromLangs = array_intersect($froms, $LCIDs);
					if (count($fromLangs))
					{
						$result[$LCID] = $fromLangs;
					}
				}
			}

			if (count($result))
			{
				return $result;
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function hasI18nDocumentsSynchro()
	{
		if ($this->i18nDocumentsSynchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->i18nDocumentsSynchro !== false;
	}

	/**
	 * @return array string : string[]
	 */
	public function getI18nDocumentsSynchro()
	{
		return $this->hasI18nDocumentsSynchro() ? $this->i18nDocumentsSynchro : array();
	}

	/**
	 * @return boolean
	 */
	public function hasI18nKeysSynchro()
	{
		if ($this->i18nKeysSynchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->i18nKeysSynchro !== false;
	}

	/**
	 * @return array string : string[]
	 */
	public function getI18nKeysSynchro()
	{
		return $this->hasI18nKeysSynchro() ? $this->i18nKeysSynchro : array();
	}

	/**
	 * @return boolean
	 */
	public function isMultiLangEnabled()
	{
		return count($this->supportedLCIDs) > 1;
	}

	/**
	 * Converts a two characters lang code to a LCID.
	 * @api
	 * @param string $lang
	 * @return string
	 */
	public function getLCIDByLang($lang)
	{
		if ($this->langMap === null)
		{
			$this->langMap = $this->getConfiguration()->getEntry('i18n/langs', array());
		}
		
		$supportedLang = array_search($lang, $this->langMap);
		if ($supportedLang === false)
		{
			foreach ($this->getSupportedLCIDs() as $LCID)
			{
				if (strpos($LCID, $lang) === 0)
				{
					$this->langMap[$LCID] = $lang;
					return $LCID;
				}
			}
			return strtolower($lang) . '_' . strtoupper($lang);
		}
		return $supportedLang;
	}

	/**
	 * Converts a LCID to a two characters lang code.
	 * @api
	 * @param string $LCID
	 * @return string
	 */
	public function getLangByLCID($LCID)
	{
		if ($this->langMap === null)
		{
			$this->langMap = $this->getConfiguration()->getEntry('i18n/langs', array());
		}

		if (!isset($this->langMap[$LCID]))
		{
			if (strlen($LCID) === 5)
			{
				$this->langMap[$LCID] = strtolower(substr($LCID, 0, 2));
			}
			else
			{
				throw new \InvalidArgumentException('Invalid LCID: ' . $LCID);
			}
			
		}
		return $this->langMap[$LCID];
	}

	/**
	 * For example: trans('f.boolean.true')
	 * @api
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, html, attr
	 * @param array $replacements
	 * @return string | $cleanKey
	 */
	public function trans($cleanKey, $formatters = array(), $replacements = array())
	{
		return $this->formatKey($this->getLCID(), $cleanKey, $formatters, $replacements);
	}

	/**
	 * For example: formatKey('fr_FR', 'f.boolean.true')
	 * @api
	 * @param string $LCID
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatKey($LCID, $cleanKey, $formatters = array(), $replacements = array())
	{
		if ($cleanKey instanceof \Change\I18n\PreparedKey)
		{
			$preparedKey = $cleanKey;
			$preparedKey->mergeFormatters($formatters);
			$preparedKey->mergeReplacements($replacements);
		}
		else
		{
			$preparedKey = new \Change\I18n\PreparedKey($cleanKey, $formatters, $replacements);
		}

		if ($preparedKey->isValid())
		{
			$q = $this->getTranslateQuery();
			$q->bindParameter('lang', $LCID)
				->bindParameter('id', $preparedKey->getId())
				->bindParameter('key_path', $preparedKey->getPath());
			
			$result = $q->getFirstResult();
			if ($result)
			{
				$content = strval($result['content']);
				$format = $result['format'];
				return $this->formatText($LCID, $content, $format, $preparedKey->getFormatters(), $preparedKey->getReplacements());
			}
			$this->logKeyNotFound($preparedKey->getKey(), $LCID);
			return $preparedKey->getKey();
		}
		else
		{
			return $preparedKey->getKey();
		}
	}
	
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $translateQuery;
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getTranslateQuery()
	{
		if ($this->translateQuery === null)
		{
			$qb = $this->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->translateQuery = $qb->select('content', 'format')->from($fb->getLocaleTable())
				->where($fb->logicAnd(
					$fb->eq($fb->column('lang'), $fb->parameter('lang', $qb)),
					$fb->eq($fb->column('id'), $fb->parameter('id', $qb)),
					$fb->eq($fb->column('key_path'), $fb->parameter('key_path', $qb))
					))->query();
		}
		return $this->translateQuery;
	}
	
	/**
	 * For example: formatText('fr_FR', 'My text.')
	 * @api
	 * @param string $LCID
	 * @param string $text
	 * @param string $format 'TEXT' or 'HTML'
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatText($LCID, $text, $format = 'TEXT', $formatters = array(), $replacements = array())
	{
		if (count($replacements))
		{
			$search = array();
			$replace = array();
			foreach ($replacements as $key => $value)
			{
				$search[] = '{' . $key . '}';
				$replace[] = $value;
			}
			$text = str_replace($search, $replace, $text);
		}
		
		if (count($formatters))
		{
			foreach ($formatters as $formatter)
			{
				if ($formatter === 'raw' || $formatter === $this->ignoreTransform[$format])
				{
					continue;
				}
				if (isset($this->transformers[$formatter]))
				{
					$text = call_user_func(array($this, $this->transformers[$formatter]), $text, $LCID);
				}
				else
				{
					$this->logging->warn(__METHOD__ . ' Invalid formatter ' . $formatter);
				}
			}
		}
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function translateText($text)
	{
		if (empty($text))
		{
			return $text;
		}
		if (preg_match_all('/\$\{(trans|transui|transdata):([^}]*)\}/', $text, $matches, PREG_SET_ORDER))
		{
			$search = array();
			$replace = array();
			foreach ($matches as $infos)
			{
				$search[] = $infos[0];
				// TODO: transdata?
				$replace[] = $this->formatKey($this->getLCID(), $this->prepareKeyFromTransString($infos[2]));
			}
			$text = str_replace($search, $replace, $text);
		}
		return $text;
	}

	/**
	 * @param string $transString
	 * @return \Change\I18n\PreparedKey
	 */
	public function prepareKeyFromTransString($transString)
	{
		$formatters = array();
		$replacements = array();
		$parts = explode(',', $transString);
		$count = count($parts);
		for ($i = 1; $i < $count; $i++)
		{
			$data = trim($parts[$i]);
			if (strlen($data) == 0)
			{
				continue;
			}
			if (strpos($data, '='))
			{
				$subParts = explode('=', $data);
				if (count($subParts) == 2)
				{
					list ($name, $value) = $subParts;
					$name = trim($name);
					$value = trim($value);
					$replacements[$name] = strlen($value) === 0 ? '' : $value;
				}
			}
			else
			{
				$data = strtolower($data);
				$formatters[] = $data;
			}
		}
		return new \Change\I18n\PreparedKey(trim($parts[0]), $formatters, $replacements);
	}

	/**
	 * @param string $key
	 * @param string $lcid
	 */
	protected function logKeyNotFound($key, $lcid)
	{
		$stringLine = $lcid . '/' . $key;
		$this->logging->namedLog($stringLine, 'keynotfound');
	}
	
	// Dates.

	/**
	 * @api
	 * @param string $LCID
	 * @return string
	 */
	public function getDateFormat($LCID)
	{
		if ($this->uiDateFormat)
		{
			return $this->uiDateFormat;
		}
		return $this->formatKey($LCID, 'c.date.default-date-format');
	}
	
	/**
	 * @api
	 * @param string $dateFormat
	 */
	public function setDateFormat($dateFormat)
	{
		$this->uiDateFormat = $dateFormat;
	}
	
	/**
	 * @api
	 * @param string $LCID
	 * @return string
	 */
	public function getDateTimeFormat($LCID)
	{
		if ($this->uiDateTimeFormat)
		{
			return $this->uiDateTimeFormat;
		}
		return $this->formatKey($LCID, 'c.date.default-datetime-format');
	}
	
	/**
	 * @api
	 * @param string $uiDateTimeFormat
	 */
	public function setDateTimeFormat($dateTimeFormat)
	{
		$this->uiDateTimeFormat = $dateTimeFormat;
	}
	
	/**
	 * @api
	 * @return \DateTimeZone
	 */
	public function getTimeZone()
	{
		if ($this->uiTimeZone)
		{
			return $this->uiTimeZone;
		}
		return new \DateTimeZone($this->getConfiguration()->getEntry('i18n/default-timezone'));
	}
	
	/**
	 * @api
	 * @param \DateTimeZone|string $timeZone
	 */
	public function setTimeZone($timeZone)
	{
		if (!($timeZone instanceof \DateTimeZone))
		{
			$timeZone = new \DateTimeZone($timeZone);
		}
		$this->uiTimeZone = $timeZone;
	}
	
	/**
	 * @api
	 * @param \DateTime $gmtDate
	 * @return string
	 */
	public function transDate(\DateTime $gmtDate)
	{
		$LCID = $this->getLCID();
		return $this->formatDate($LCID, $gmtDate, $this->getDateFormat($LCID));
	}
	
	/**
	 * @api
	 * @param \DateTime $date
	 * @return string
	 */
	public function transDateTime(\DateTime $gmtDate)
	{
		$LCID = $this->getLCID();
		return $this->formatDate($LCID, $gmtDate, $this->getDateTimeFormat($LCID));
	}
	
	/**
	 * Format a date. The format parameter 
	 * @api
	 * @param string $LCID
	 * @param \DateTime $date
	 * @param string $format
	 * @param \DateTimeZone $timeZone
	 */
	public function formatDate($LCID, \DateTime $gmtDate, $format, $timeZone = null)
	{
		if (!$timeZone)
		{
			$timeZone = $this->getTimeZone();
		}
		$datefmt = new \IntlDateFormatter($LCID, null, null, $timeZone->getName(), \IntlDateFormatter::GREGORIAN, $format);
		return $datefmt->format($this->toLocalDateTime($gmtDate));
	}
	
	/**
	 * @api
	 * @param string $date
	 */
	public function getGMTDateTime($date)
	{
		return new \DateTime($date, new \DateTimeZone('UTC'));
	}
	
	/**
	 * @api
	 * @param \DateTime $localDate
	 * @param string $timeZone
	 */
	public function toGMTDateTime($localDate)
	{
		return $localDate->setTimezone(new \DateTimeZone('UTC'));
	}
	
	/**
	 * @api
	 * @param string $date
	 */
	public function getLocalDateTime($date)
	{
		return new \DateTime($date, $this->getTimeZone());
	}
	
	/**
	 * @api
	 * @param \DateTime $localDate
	 * @param string $timeZone
	 */
	public function toLocalDateTime($localDate)
	{
		return $localDate->setTimezone($this->getTimeZone());
	}

	// Transformers.
	
	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformLab($text, $LCID)
	{
		return $text . (substr($LCID, 0, 2) === 'fr' ? ' :' : ':');
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformUc($text, $LCID)
	{
		return \Change\Stdlib\String::toUpper($text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformUcf($text, $LCID)
	{
		return \Change\Stdlib\String::ucfirst($text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformUcw($text, $LCID)
	{
		return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformLc($text, $LCID)
	{
		return \Change\Stdlib\String::toLower($text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformJs($text, $LCID)
	{
		return str_replace(array("\\", "\t", "\n", "\"", "'"), array("\\\\", "\\t", "\\n", "\\\"", "\\'"), $text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformHtml($text, $LCID)
	{
		return nl2br(htmlspecialchars($text, ENT_COMPAT, 'UTF-8'));
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformText($text, $LCID)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::htmlToText($text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformAttr($text, $LCID)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::textToAttribute($text);
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformSpace($text, $LCID)
	{
		return ' ' . $text . ' ';
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformEtc($text, $LCID)
	{
		return $text . '...';
	}
}
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
	protected $LCID_BY_LANG = null;

	/**
	 * @var array
	 */
	protected $ignoreTransform;

	/**
	 * @var array
	 */
	protected $transformers;

	/**
	 * @var string two lower-cased letters code, ex: "fr"
	 */
	protected $uilang;

	/**
	 * @var string[] two lower-cased letters codes, ex: "fr"
	 */
	protected $m_supportedLanguages = array();

	/**
	 * @var array
	 */
	protected $m_i18n_documents_synchro = null;

	/**
	 * @var array
	 */
	protected $m_i18n_keys_synchro = null;

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @param \Change\Configuration\Configuration $config
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function __construct(\Change\Configuration\Configuration $config, \Change\Db\DbProvider $dbProvider)
	{
		$this->configuration = $config;
		$this->dbProvider = $dbProvider;
		$this->ignoreTransform = array('TEXT' => 'raw', 'HTML' => 'html');

		$this->transformers = array('lab' => 'transformLab', 'uc' => 'transformUc', 'ucf' => 'transformUcf', 'lc' => 'transformLc',
			'js' => 'transformJs', 'html' => 'transformHtml', 'text' => 'transformText', 'attr' => 'transformAttr', 'space' => 'transformSpace',
			'etc' => 'transformEtc', 'ucw' => 'transformUcw');

		$this->m_supportedLanguages = $config->getEntry('i18n/supported-languages', array('fr'));
	}
	
	/**
	 * @return \Change\Mvc\Controller
	 */
	protected function getController()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getController();
	}

	/**
	 * Get all supported language codes.
	 * @api
	 * @return string[] two lower-cased letters codes, ex: "fr"
	 */
	public function getSupportedLanguages()
	{
		return $this->m_supportedLanguages;
	}

	/**
	 * Get the default language code.
	 * @api
	 * @return string two lower-cased letters code, ex: "fr"
	 */
	public function getDefaultLang()
	{
		return $this->m_supportedLanguages[0];
	}

	/**
	 * Get the UI language code.
	 * @api
	 * @return string two lower-cased letters code, ex: "fr"
	 */
	public function getLang()
	{
		if ($this->uilang === null)
		{
			$uilang = $this->getController()->getStorage()->readForUser('uilang');
			$this->setLang($uilang ? $uilang : $this->getDefaultLang());
		}
		return $this->uilang;
	}

	/**
	 * Set the UI language code.
	 * @api
	 * @throws \InvalidArgumentException if the lang is not supported
	 * @param string $lang two lower-cased letters code, ex: "fr"
	 */
	public function setLang($lang)
	{
		if (!in_array($lang, $this->getSupportedLanguages()))
		{
			throw new \InvalidArgumentException('Not supported language: ' . $lang);
		}
		$this->uilang = $lang;
	}

	/**
	 * Loads the i18n synchro configuration.
	 */
	protected function loadI18nSynchroConfiguration()
	{
		$data = $this->configuration->getEntry('i18n/synchro/documents', null);
		$this->m_i18n_documents_synchro = $this->cleanI18nSynchroConfiguration($data);
		$data = $this->configuration->getEntry('i18n/synchro/keys', null);
		$this->m_i18n_keys_synchro = $this->cleanI18nSynchroConfiguration($data);
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
			$langs = $this->getSupportedLanguages();
			foreach ($data as $lang => $froms)
			{
				if (in_array($lang, $langs))
				{
					$fromLangs = array_intersect($froms, $langs);
					if (count($fromLangs))
					{
						$result[$lang] = $fromLangs;
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
		if ($this->m_i18n_documents_synchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->m_i18n_documents_synchro !== false;
	}

	/**
	 * @return array string : string[]
	 */
	public function getI18nDocumentsSynchro()
	{
		return $this->hasI18nDocumentsSynchro() ? $this->m_i18n_documents_synchro : array();
	}

	/**
	 * @return boolean
	 */
	public function hasI18nKeysSynchro()
	{
		if ($this->m_i18n_keys_synchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->m_i18n_keys_synchro !== false;
	}

	/**
	 * @return array string : string[]
	 */
	public function getI18nKeysSynchro()
	{
		return $this->hasI18nKeysSynchro() ? $this->m_i18n_keys_synchro : array();
	}

	/**
	 * @return boolean
	 */
	public function isMultiLangEnabled()
	{
		return count($this->m_supportedLanguages) > 1;
	}

	/**
	 * Converts a two characters lang code to a LCID.
	 * @api
	 * @param string $langCode
	 * @return string
	 */
	public function getLCID($langCode)
	{
		if ($this->LCID_BY_LANG === null)
		{
			$this->LCID_BY_LANG = $this->configuration->getEntry('i18n/lcids', array());
		}

		if (!isset($this->LCID_BY_LANG[$langCode]))
		{
			if (strlen($langCode) === 2)
			{
				$this->LCID_BY_LANG[$langCode] = strtolower($langCode) . '_' . strtoupper($langCode);
			}
			else
			{
				$this->LCID_BY_LANG[$langCode] = strtolower($langCode);
			}
		}
		return $this->LCID_BY_LANG[$langCode];
	}

	/**
	 * Converts a LCID to a two characters lang code.
	 * @api
	 * @param string $lcid
	 * @return string
	 */
	public function getCode($lcid)
	{
		if ($this->LCID_BY_LANG === null)
		{
			$this->LCID_BY_LANG = $this->configuration->getEntry('i18n/lcids', array());
		}

		$code = array_search($lcid, $this->LCID_BY_LANG);
		if ($code === false)
		{
			return substr($lcid, 0, 2);
		}
		return $code;
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
		return $this->formatKey($this->getLang(), $cleanKey, $formatters, $replacements);
	}

	/**
	 * For example: formatKey('fr', 'f.boolean.true')
	 * @api
	 * @param string $lang
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatKey($lang, $cleanKey, $formatters = array(), $replacements = array())
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
			$lcid = $this->getLCID($lang);
			list ($content, $format) = $this->dbProvider->translate($lcid, $preparedKey->getId(), $preparedKey->getPath());
			if ($content !== null)
			{
				return $this->formatText($lang, $content, $format, $preparedKey->getFormatters(), $preparedKey->getReplacements());
			}
			$this->logKeyNotFound($preparedKey->getKey(), $lcid);
			return $preparedKey->getKey();
		}
		else
		{
			return $preparedKey->getKey();
		}
	}
	
	/**
	 * For example: formatText('fr', 'My text.')
	 * @api
	 * @param string $lang
	 * @param string $text
	 * @param string $format 'TEXT' or 'HTML'
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatText($lang, $text, $format = 'TEXT', $formatters = array(), $replacements = array())
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
					$text = call_user_func(array($this, $this->transformers[$formatter]), $text, $lang);
				}
				else
				{
					\Change\Application::getInstance()->getApplicationServices()->getLogging()->warn(__METHOD__ . ' Invalid formatter ' . $formatter);
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
				$lang = ($infos[1] === 'transdata') ? $this->getLang() : $this->getLang();
				$replace[] = $this->formatKey($lang, $this->prepareKeyFromTransString($infos[2]));
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
	 * @param string $lang
	 */
	protected function logKeyNotFound($key, $lang)
	{
		$application = \Change\Application::getInstance();
		if ($application->inDevelopmentMode())
		{
			$stringLine = $lang . '/' . $key;
			$application->getApplicationServices()->getLogging()->namedLog($stringLine, 'keynotfound');
		}
	}
	
	// Dates.
	
	/**
	 * Resets the profile values.
	 */
	public function resetProfile()
	{
		$this->profile = array('date' => array(), 'datetime' => array(), 'timezone' => null);
	}
	
	/**
	 * @return array
	 */
	protected function getProfileValues()
	{
		$pref = $this->getController()->getStorage()->readForUser('profilesvalues');
		return is_array($pref) ? $pref : array();
	}
	
	/**
	 * @api
	 * @param string $lang
	 * @return string
	 */
	public function getDateFormat($lang)
	{
		if (!isset($this->profile['date'][$lang]))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['dateformat']))
			{
				$this->profile['date'][$lang] = $prefs['dateformat'];
			}
			else
			{
				$this->profile['date'][$lang] = $this->formatKey($lang, 'c.date.default-date-format');
			}
		}
		return $this->profile['date'][$lang];
	}
	
	/**
	 * @api
	 * @param string $lang
	 * @return string
	 */
	public function getDateTimeFormat($lang)
	{
		if (!isset($this->profile['datetime'][$lang]))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['datetimeformat']))
			{
				$this->profile['datetime'][$lang] = $prefs['datetimeformat'];
			}
			else
			{
				$this->profile['datetime'][$lang] = $this->formatKey($lang, 'c.date.default-datetime-format');
			}
		}
		return $this->profile['datetime'][$lang];
	}
	
	/**
	 * @api
	 * @return \DateTimeZone
	 */
	public function getTimeZone()
	{
		if (!isset($this->profile['timezone']))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['timezone']) && !empty($prefs['timezone']))
			{
				$this->profile['timezone'] = $prefs['timezone'];
			}
			else
			{
				$this->profile['timezone'] = DEFAULT_TIMEZONE;
			}
		}
		return new \DateTimeZone($this->profile['timezone']);
	}
	
	/**
	 * @api
	 * @param \DateTime $gmtDate
	 * @return string
	 */
	public function transDate(\DateTime $gmtDate)
	{
		$lang = $this->getLang();
		return $this->formatDate($lang, $gmtDate, $this->getDateFormat($lang));
	}
	
	/**
	 * @api
	 * @param \DateTime $date
	 * @return string
	 */
	public function transDateTime(\DateTime $gmtDate)
	{
		$lang = $this->getLang();
		return $this->formatDate($lang, $gmtDate, $this->getDateTimeFormat($lang));
	}
	
	/**
	 * Format a date. The format parameter 
	 * @api
	 * @param string $lang
	 * @param \DateTime $date
	 * @param string $format
	 * @param \DateTimeZone $timeZone
	 */
	public function formatDate($lang, \DateTime $gmtDate, $format, $timeZone = null)
	{
		if (!$timeZone)
		{
			$timeZone = $this->getTimeZone();
		}
		$lcid = $this->getLCID($lang);
		$datefmt = new \IntlDateFormatter($lcid, null, null, $timeZone->getName(), \IntlDateFormatter::GREGORIAN, $format);
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
	 * @param string $lang
	 * @return string
	 */
	public function transformLab($text, $lang)
	{
		return $text . ($lang == 'fr' ? ' :' : ':');
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformUc($text, $lang)
	{
		return \Change\Stdlib\String::toUpper($text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformUcf($text, $lang)
	{
		return \Change\Stdlib\String::ucfirst($text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformUcw($text, $lang)
	{
		return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformLc($text, $lang)
	{
		return \Change\Stdlib\String::toLower($text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformJs($text, $lang)
	{
		return str_replace(array("\\", "\t", "\n", "\"", "'"), array("\\\\", "\\t", "\\n", "\\\"", "\\'"), $text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformHtml($text, $lang)
	{
		return nl2br(htmlspecialchars($text, ENT_COMPAT, 'UTF-8'));
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformText($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::htmlToText($text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformAttr($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::textToAttribute($text);
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformSpace($text, $lang)
	{
		return ' ' . $text . ' ';
	}

	/**
	 * @param string $text
	 * @param string $lang
	 * @return string
	 */
	public function transformEtc($text, $lang)
	{
		return $text . '...';
	}
}
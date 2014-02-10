<?php
namespace Change\I18n;

use Zend\Stdlib\ErrorHandler;
use Zend\Stdlib\Glob;

/**
 * @api
 * @name \Change\I18n\I18nManager
 */
class I18nManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_KEY_NOT_FOUND = 'key-not-found';
	const EVENT_FORMATTING = 'formatting';
	const EVENT_MANAGER_IDENTIFIER = 'I18n';

	/**
	 * @var array
	 */
	protected $langMap = null;

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
	protected $i18nSynchro = null;

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @var \Change\Plugins\PluginManager
	 */
	protected $pluginManager;

	/**
	 * @var array<string, string>
	 */
	protected $packageList;

	/**
	 * @var array
	 */
	protected $loadedPackages = [];

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		$this->supportedLCIDs = $this->configuration->getEntry('Change/I18n/supported-lcids');
		if (!is_array($this->supportedLCIDs) || count($this->supportedLCIDs) === 0)
		{
			$this->supportedLCIDs = array('fr_FR');
		}
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Workspace $workspace
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		return $this->workspace;
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
	 * @param \Change\Plugins\PluginManager $pluginManager
	 */
	public function setPluginManager(\Change\Plugins\PluginManager $pluginManager)
	{
		$this->pluginManager = $pluginManager;
	}

	/**
	 * @return \Change\Plugins\PluginManager
	 */
	public function getPluginManager()
	{
		return $this->pluginManager;
	}

	/**
	 * @api
	 * @param string $LCID
	 * @return boolean
	 */
	public function isValidLCID($LCID)
	{
		return is_string($LCID) && preg_match('/^[a-z]{2}_[A-Z]{2}$/', $LCID);
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
	 * @api
	 * @param string $LCID
	 * @return boolean
	 */
	public function isSupportedLCID($LCID)
	{
		return ($this->isValidLCID($LCID) && in_array($LCID, $this->getSupportedLCIDs()));
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function supportsMultipleLCIDs()
	{
		return count($this->supportedLCIDs) > 1;
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
			throw new \InvalidArgumentException('Not supported LCID: ' . $LCID, 80000);
		}
		$this->uiLCID = $LCID;
	}

	/**
	 * Loads the i18n synchro configuration.
	 */
	protected function loadI18nSynchroConfiguration()
	{
		$data = $this->getConfiguration()->getEntry('Change/I18n/synchro/keys', null);
		$this->i18nSynchro = $this->cleanI18nSynchroConfiguration($data);
	}

	/**
	 * Clean i18n synchro configuration.
	 * @see loadI18nSynchroConfiguration()
	 * @param array $data
	 * @return array|bool
	 */
	protected function cleanI18nSynchroConfiguration($data)
	{
		$result = array();
		if (is_array($data) && count($data))
		{
			$LCIDs = $this->getSupportedLCIDs();
			foreach ($data as $LCID => $fromLCIDs)
			{
				if (in_array($LCID, $LCIDs))
				{
					$fromLCIDs = array_intersect($fromLCIDs, $LCIDs);
					if (count($fromLCIDs))
					{
						$result[$LCID] = $fromLCIDs;
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
	public function hasI18nSynchro()
	{
		if ($this->i18nSynchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->i18nSynchro !== false;
	}

	/**
	 * @return array string : string[]
	 */
	public function getI18nSynchro()
	{
		return $this->hasI18nSynchro() ? $this->i18nSynchro : array();
	}

	/**
	 * Converts a LCID to a two characters lang code.
	 * @param string $LCID
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getLangByLCID($LCID)
	{
		if ($this->langMap === null)
		{
			$this->langMap = $this->getConfiguration()->getEntry('Change/I18n/langs', array());
		}

		if (!isset($this->langMap[$LCID]))
		{
			if (strlen($LCID) === 5)
			{
				$this->langMap[$LCID] = strtolower(substr($LCID, 0, 2));
			}
			else
			{
				throw new \InvalidArgumentException('Not supported LCID: ' . $LCID, 80000);
			}
		}
		return $this->langMap[$LCID];
	}

	/**
	 * For example: trans('c.date.default_date_format')
	 * @api
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, html, attr
	 * @param array $replacements
	 * @return string | $cleanKey
	 */
	public function trans($cleanKey, $formatters = array(), $replacements = array())
	{
		return $this->transForLCID($this->getLCID(), $cleanKey, $formatters, $replacements);
	}

	/**
	 * For example: transForLCID('fr_FR', 'c.date.default_date_format')
	 * @api
	 * @param string $LCID
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 * @return string
	 */
	public function transForLCID($LCID, $cleanKey, $formatters = array(), $replacements = array())
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

		$textKey = $preparedKey->getKey();
		if ($preparedKey->isValid())
		{
			$parts = explode('.', $textKey);
			$id = array_pop($parts);
			$packageName = implode('.', $parts);

			$translations = $this->getTranslationsForPackage($packageName, $LCID);

			if ($translations === false)
			{
				$synchro = $this->getI18nSynchro();
				if (isset($synchro[$LCID]))
				{
					foreach ($synchro[$LCID] as $referenceLCID)
					{
						$translations = $this->getTranslationsForPackage($packageName, $referenceLCID);
						if ($translations !== false)
						{
							break;
						}
					}
				}
			}

			if (is_array($translations) && isset($translations[$id]))
			{
				return $this->formatText($LCID, $translations[$id], $preparedKey->getFormatters(),
					$preparedKey->getReplacements());
			}
			return $this->dispatchKeyNotFound($preparedKey, $LCID);
		}
		return $textKey;
	}

	/**
	 * @param string $packageName
	 * @return array
	 */
	public function getTranslationsForPackage($packageName, $LCID)
	{
		if (!isset($this->loadedPackages[$LCID][$packageName]))
		{

			// Load the  package for the corresponding LCID
			$compiledPackagePath = $this->getWorkspace()->compilationPath('I18n', $LCID, $packageName . '.ser');
			if ($this->getConfiguration()->inDevelopmentMode())
			{
				$this->recompileIfNeeded($packageName, $LCID);
			}

			if (file_exists($compiledPackagePath))
			{
				$this->loadedPackages[$LCID][$packageName] = unserialize(file_get_contents($compiledPackagePath));
			}
			else
			{
				$this->loadedPackages[$LCID][$packageName] = false;
			}
		}
		return $this->loadedPackages[$LCID][$packageName];
	}

	/**
	 * For example: formatText('fr_FR', 'My text.')
	 * @api
	 * @param string $LCID
	 * @param string $text
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements * @internal param string $format 'TEXT' or 'HTML'
	 * @return string
	 */
	public function formatText($LCID, $text, $formatters = array(), $replacements = array())
	{
		if (count($replacements))
		{
			$search = array();
			$replace = array();
			foreach ($replacements as $key => $value)
			{
				$search[] = '$' . $key . '$';
				$replace[] = $value;
			}
			$text = str_ireplace($search, $replace, $text);
		}

		if (count($formatters))
		{
			$text = $this->dispatchFormatting($text, $formatters, $LCID);
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

	// Events.
	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/I18n');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_KEY_NOT_FOUND, array($this, 'onKeyNotFound'), 5);
		$eventManager->attach(static::EVENT_FORMATTING, array($this, 'onFormatting'), 5);
	}

	/**
	 * @param \Change\I18n\PreparedKey $preparedKey
	 * @param string $LCID
	 * @return string
	 */
	protected function dispatchKeyNotFound($preparedKey, $LCID)
	{
		$args = array('preparedKey' => $preparedKey, 'LCID' => $LCID);
		$event = new \Change\Events\Event(static::EVENT_KEY_NOT_FOUND, $this, $args);
		$callback = function ($result)
		{
			return is_string($result);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && is_string($results->last())) ? $results->last() : $event->getParam('text');
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onKeyNotFound($event)
	{
		$key = $event->getParam('preparedKey')->getKey();
		$stringLine = $event->getParam('LCID') . '/' . $key;
		$event->getTarget()->getLogging()->namedLog($stringLine, 'keynotfound');
		$event->setParam('text', $key);
	}

	/**
	 * @param string $text
	 * @param string[] $formatters
	 * @param string $LCID
	 * @internal param string $textFormat
	 * @return string
	 */
	protected function dispatchFormatting($text, $formatters, $LCID)
	{
		$args = array('text' => $text, 'formatters' => $formatters, 'LCID' => $LCID);
		$event = new \Change\Events\Event(static::EVENT_FORMATTING, $this, $args);
		$callback = function ($result)
		{
			return is_string($result);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && is_string($results->last())) ? $results->last() : $event->getParam('text');
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onFormatting($event)
	{
		$text = $event->getParam('text');
		$LCID = $event->getParam('LCID');
		foreach ($event->getParam('formatters') as $formatter)
		{
			$callable = array($this, 'transform' . ucfirst($formatter));
			if (is_callable($callable))
			{
				$text = call_user_func($callable, $text, $LCID);
			}
			else
			{
				$this->logging->info(__METHOD__ . ' Unknown formatter ' . $formatter);
			}
		}
		$event->setParam('text', $text);
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
		return $this->transForLCID($LCID, 'c.date.default_date_format');
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
		return $this->transForLCID($LCID, 'c.date.default_datetime_format');
	}

	/**
	 * @api
	 * @param string $dateTimeFormat
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
		return new \DateTimeZone($this->getConfiguration()->getEntry('Change/I18n/default-timezone'));
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
	 * @param \DateTime $gmtDate
	 * @return string
	 */
	public function transDateTime(\DateTime $gmtDate)
	{
		$LCID = $this->getLCID();
		return $this->formatDate($LCID, $gmtDate, $this->getDateTimeFormat($LCID));
	}

	/**
	 * Format a date.
	 * @api
	 * @param string $LCID
	 * @param \DateTime $gmtDate
	 * @param string $format using this syntax: http://userguide.icu-project.org/formatparse/datetime
	 * @param \DateTimeZone $timeZone
	 * @return string
	 */
	public function formatDate($LCID, \DateTime $gmtDate, $format, $timeZone = null)
	{
		if (!$timeZone)
		{
			$timeZone = $this->getTimeZone();
		}
		$tmpDate = clone $gmtDate; // To not alter $gmtDate.
		$dateFormatter = new \IntlDateFormatter($LCID, null, null, $timeZone->getName(), \IntlDateFormatter::GREGORIAN, $format);
		return $dateFormatter->format($this->toLocalDateTime($tmpDate));
	}

	/**
	 * @api
	 * @param string $date
	 * @return \DateTime
	 */
	public function getGMTDateTime($date)
	{
		return new \DateTime($date, new \DateTimeZone('UTC'));
	}

	/**
	 * @api
	 * @param \DateTime $localDate
	 * @return \DateTime
	 */
	public function toGMTDateTime($localDate)
	{
		return $localDate->setTimezone(new \DateTimeZone('UTC'));
	}

	/**
	 * @api
	 * @param string $date
	 * @return \DateTime
	 */
	public function getLocalDateTime($date)
	{
		return new \DateTime($date, $this->getTimeZone());
	}

	/**
	 * @api
	 * @param \DateTime $localDate
	 * @return \DateTime
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
		if ($text === null)
		{
			return '';
		}
		$text = str_replace(array('</div>', '</p>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '</tr>'),
			PHP_EOL, $text);
		$text = str_replace(array('</td>', '</th>'), "\t", $text);
		$text = preg_replace('/<li[^>]*>/', ' * ', $text);
		$text = preg_replace('/<br[^>]*>/', PHP_EOL, $text);
		$text = preg_replace('/<hr[^>]*>/', "------" . PHP_EOL, $text);
		$text = preg_replace(array('/<a[^>]+href="([^"]+)"[^>]*>([^<]+)<\/a>/i', '/<img' . '[^>]+alt="([^"]+)"[^>]*\/>/i'),
			array('$2 [$1]', PHP_EOL . "[$1]" . PHP_EOL), $text);
		$text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
		return $text;
	}

	/**
	 * @param string $text
	 * @param string $LCID
	 * @return string
	 */
	public function transformAttr($text, $LCID)
	{
		return htmlspecialchars(str_replace(array("\t", "\n"), array("&#09;", "&#10;"), $text), ENT_COMPAT, 'UTF-8');
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

	public function removeCompiledCoreI18nFiles()
	{
		$localeFilePattern = $this->getWorkspace()->compilationPath('I18n', '*', 'c.*.ser');
		foreach(Glob::glob($localeFilePattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $fileName)
		{
			ErrorHandler::start();
			unlink($fileName);
			ErrorHandler::stop();
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function removeCompiledPluginI18nFiles(\Change\Plugins\Plugin $plugin)
	{
		$baseName = implode('.', [
			$plugin->getType() == \Change\Plugins\Plugin::TYPE_MODULE ? 'm' : 't',
			\Change\Stdlib\String::toLower($plugin->getVendor()),
			\Change\Stdlib\String::toLower($plugin->getShortName()), '*.ser']);
		$localeFilePattern = $this->getWorkspace()->compilationPath('I18n', '*', $baseName);
		foreach(Glob::glob($localeFilePattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $fileName)
		{
			ErrorHandler::start();
			unlink($fileName);
			ErrorHandler::stop();
		}
	}

	public function compileCoreI18nFiles()
	{
		$this->removeCompiledCoreI18nFiles();

		// Compile the default localization
		$defaultLCID = 'fr_FR';
		$defaultI18n = [];

		if ($defaultLCID)
		{
			$this->processCoreI18nFilesForLCID($defaultLCID, $defaultI18n);
		}

		$allI18n = [];
		foreach ($this->getSupportedLCIDs() as $LCID)
		{
			$allI18n[$LCID] = $defaultI18n;

			if ($LCID !== $defaultLCID)
			{
				$this->processCoreI18nFilesForLCID($LCID, $allI18n[$LCID]);
			}
		}

		if ($defaultLCID)
		{
			$allI18n[$defaultLCID] = $defaultI18n;
		}

		foreach ($allI18n as $LCID => $package)
		{
			foreach ($package as $name => $content)
			{
				$serializedFileName = $name . '.ser';
				\Change\Stdlib\File::write($this->getWorkspace()->compilationPath('I18n', $LCID, $serializedFileName), serialize($content));
			}
		}

	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function compilePluginI18nFiles(\Change\Plugins\Plugin $plugin)
	{
		$this->removeCompiledPluginI18nFiles($plugin);

		// Compile the default localization
		$defaultLCID = $plugin->getDefaultLCID();
		$defaultI18n = [];

		if ($defaultLCID)
		{
			$this->processPluginI18nFilesForLCID($plugin, $defaultLCID, $defaultI18n);
		}

		$allI18n = [];
		foreach ($this->getSupportedLCIDs() as $LCID)
		{
			$allI18n[$LCID] = $defaultI18n;

			if ($LCID !== $defaultLCID)
			{
				$this->processPluginI18nFilesForLCID($plugin, $LCID, $allI18n[$LCID]);
			}
		}

		if ($defaultLCID)
		{
			$allI18n[$defaultLCID] = $defaultI18n;
		}

		foreach ($allI18n as $LCID => $package)
		{
			foreach ($package as $name => $content)
			{
				$serializedFileName = $name . '.ser';
				\Change\Stdlib\File::write($this->getWorkspace()->compilationPath('I18n', $LCID, $serializedFileName), serialize($content));
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param string $locale
	 * @return array
	 */
	protected function getI18nFilePathsForPlugin(\Change\Plugins\Plugin $plugin, $locale)
	{
		$localeFilePattern = implode(DIRECTORY_SEPARATOR, [$plugin->getAssetsPath(), 'I18n', $locale, '*.json']);
		return Glob::glob($localeFilePattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param string $locale
	 * @return string
	 */
	protected function getPluginI18nPackageNameByFilename(\Change\Plugins\Plugin $plugin, $fileName)
	{
		$fInfo = new \SplFileInfo($fileName);
		return implode('.', [
			$plugin->getType() == \Change\Plugins\Plugin::TYPE_MODULE ? 'm' : 't',
			\Change\Stdlib\String::toLower($plugin->getVendor()),
			\Change\Stdlib\String::toLower($plugin->getShortName()),
			substr(\Change\Stdlib\String::toLower($fInfo->getFilename()), 0, -5)
				]);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param string $LCID
	 * @param array $output
	 */
	protected function processPluginI18nFilesForLCID(\Change\Plugins\Plugin $plugin, $LCID, &$output)
	{
		foreach ($this->getI18nFilePathsForPlugin($plugin, $LCID) as $filePath)
		{
			$fInfo = new \SplFileInfo($filePath);
			$packageName = $this->getPluginI18nPackageNameByFilename($plugin, $fInfo->getFilename());
			try
			{
				$decoded = \Zend\Json\Json::decode(file_get_contents($filePath), \Zend\Json\Json::TYPE_ARRAY);
			}
			catch (\Zend\Json\Exception\RuntimeException $e)
			{
				$this->getLogging()->error('Decoding failed ' . $filePath);
				$decoded = null;
			}
			if (is_array($decoded))
			{
				foreach ($decoded as $key => $data)
				{
					if (isset($data['message']))
					{
						$output[$packageName][\Change\Stdlib\String::toLower($key)] = $data['message'];
					}
				}
			}

			$overrideFilePath = $this->getWorkspace()->appPath('Override', 'I18n', $LCID, $packageName . '.json');
			if (file_exists($overrideFilePath))
			{
				try
				{
					$decoded = \Zend\Json\Json::decode(file_get_contents($overrideFilePath), \Zend\Json\Json::TYPE_ARRAY);
				}
				catch (\Zend\Json\Exception\RuntimeException $e)
				{
					$this->getLogging()->error('Decoding failed ' . $overrideFilePath);
					$decoded = null;
				}
				if (is_array($decoded))
				{
					foreach ($decoded as $key => $data)
					{
						if (isset($data['message']))
						{
							$output[$packageName][\Change\Stdlib\String::toLower($key)] = $data['message'];
						}
					}
				}
			}
		}
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param string $LCID
	 * @param array $output
	 */
	protected function processCoreI18nFilesForLCID($LCID, &$output)
	{
		$localeFilePattern = $this->getWorkspace()->changePath('Assets', 'I18n', $LCID, '*.json');
		foreach (Glob::glob($localeFilePattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $filePath)
		{
			$fInfo = new \SplFileInfo($filePath);
			$packageName = 'c.' . substr($fInfo->getFilename(), 0, -5);
			try
			{
				$decoded = \Zend\Json\Json::decode(file_get_contents($filePath), \Zend\Json\Json::TYPE_ARRAY);
			}
			catch (\Zend\Json\Exception\RuntimeException $e)
			{
				$this->getLogging()->error('Decoding failed ' . $filePath);
				$decoded = null;
			}
			if (is_array($decoded))
			{
				foreach ($decoded as $key => $data)
				{
					if (isset($data['message']))
					{
						$output[$packageName][\Change\Stdlib\String::toLower($key)] = $data['message'];
					}
				}
			}

			$overrideFilePath = $this->getWorkspace()->appPath('Override', 'I18n', $LCID, $packageName . '.json');
			if (file_exists($overrideFilePath))
			{
				try
				{
					$decoded = \Zend\Json\Json::decode(file_get_contents($overrideFilePath), \Zend\Json\Json::TYPE_ARRAY);
				}
				catch (\Zend\Json\Exception\RuntimeException $e)
				{
					$this->getLogging()->error('Decoding failed ' . $overrideFilePath);
					$decoded = null;
				}
				if (is_array($decoded))
				{
					foreach ($decoded as $key => $data)
					{
						if (isset($data['message']))
						{
							$output[$packageName][\Change\Stdlib\String::toLower($key)] = $data['message'];
						}
					}
				}
			}
		}
	}

	/**
	 * @param $packageName
	 * @param $LCID
	 */
	protected function recompileIfNeeded($packageName, $LCID)
	{
		$plugin = null;
		$compileTime = 0;
		$compiledPackagePath = $this->getWorkspace()->compilationPath('I18n', $LCID, $packageName . '.ser');
		if (file_exists($compiledPackagePath))
		{
			$compileTime = filemtime($compiledPackagePath);
		}
		$overrideTime = $compileTime;
		$originalTime = $compileTime;
		$overridePackagePath = $this->getWorkspace()->appPath('Override', 'I18n', $LCID, $packageName . '.json');
		if (file_exists($overridePackagePath))
		{
			$overrideTime = filemtime($overridePackagePath);
		}
		$parts = explode('.', $packageName);
		if ($parts[0] === 'c')
		{
			$originalFilePath = $this->getWorkspace()->changePath('Assets', 'I18n', $LCID, $parts[1] . '.json');
			if (file_exists($originalFilePath))
			{
				$originalTime = filemtime($originalFilePath);
			}
		}
		else
		{
			$plugin = $this->getPluginManager()->getPlugin($parts[0] === 'm' ? \Change\Plugins\Plugin::TYPE_MODULE : \Change\Plugins\Plugin::TYPE_THEME, $parts[1], $parts[2]);
			if ($plugin)
			{
				$originalFilePath = implode(DIRECTORY_SEPARATOR, [$plugin->getAssetsPath(), 'I18n', $LCID, $parts[3] . '.json']);
				if (file_exists($originalFilePath))
				{
					$originalTime = filemtime($originalFilePath);
				}
			}
		}
		if ($compileTime < $originalTime || $compileTime < $overrideTime)
		{
			if ($plugin)
			{
				$this->compilePluginI18nFiles($plugin);
			}
			else if ($plugin === null)
			{
				$this->compileCoreI18nFiles();
			}
		}
	}
}
<?php
namespace Change\I18n;

use Change\Events\EventsCapableTrait;
use Zend\EventManager\EventManager;

/**
 * @api
 * @name \Change\I18n\I18nManager
 */
class I18nManager implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait {
		EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const EVENT_KEY_NOT_FOUND = 'key-not-found';
	const EVENT_FORMATTING = 'formatting';
	const EVENT_MANAGER_IDENTIFIER = 'I18n';

	/**
	 * @var array
	 */
	protected $langMap = null;

	/**
	 * @var array
	 */
	protected $ignoreTransform;

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
	 * @var array<string, string>
	 */
	protected $packageList;

	/**
	 * @var \Change\I18n\DefinitionCollection[]
	 */
	protected $definitionCollections = array();

	/**
	 */
	public function __construct()
	{
		$this->ignoreTransform = array(DefinitionKey::TEXT => 'raw', DefinitionKey::HTML => 'html');
	}

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
	 * For example: trans('c.boolean.true')
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
	 * For example: transForLCID('fr_FR', 'c.boolean.true')
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
			$definitionKey = $this->getDefinitionKey($LCID, array_slice($parts, 0, -1), end($parts));
			if ($definitionKey)
			{
				$content = strval($definitionKey->getText());
				$format = $definitionKey->getFormat();
				return $this->formatText($LCID, $content, $format, $preparedKey->getFormatters(),
					$preparedKey->getReplacements());
			}
			return $this->dispatchKeyNotFound($preparedKey, $LCID);
		}
		return $textKey;
	}

	/**
	 * @param string $LCID
	 * @param string[] $pathParts
	 * @param string $id
	 * @return \Change\I18n\DefinitionKey | null
	 */
	public function getDefinitionKey($LCID, $pathParts, $id)
	{
		$key = $this->doGetDefinitionKey($LCID, $pathParts, $id);
		if ($key)
		{
			return $key;
		}

		if ($this->hasI18nSynchro())
		{
			$synchro = $this->getI18nSynchro();
			if (isset($synchro[$LCID]))
			{
				foreach ($synchro[$LCID] as $referenceLCID)
				{
					$key = $this->doGetDefinitionKey($referenceLCID, $pathParts, $id);
					if ($key)
					{
						return $key;
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param string $LCID
	 * @param string[] $pathParts
	 * @param string $id
	 * @return \Change\I18n\DefinitionKey | null
	 */
	protected function doGetDefinitionKey($LCID, $pathParts, $id)
	{
		$definitionCollection = $this->getDefinitionCollection($LCID, $pathParts);
		if ($definitionCollection === null)
		{
			return null;
		}

		$definitionCollection->load();
		if ($definitionCollection->hasDefinitionKey($id))
		{
			return $definitionCollection->getDefinitionKey($id);
		}
		else
		{
			foreach (array_reverse($definitionCollection->getIncludesPaths()) as $includePath)
			{
				$includeParts = explode('.', $includePath);
				$definitionKey = $this->getDefinitionKey($LCID, $includeParts, $id);
				if ($definitionKey !== null)
				{
					return $definitionKey;
				}
			}
		}
		return null;
	}

	/**
	 * Get all definition keys for a paths.
	 * @param string $LCID
	 * @param string[] $pathParts
	 * @return \Change\I18n\DefinitionKey[]
	 */
	public function getDefinitionKeys($LCID, $pathParts)
	{
		$keys = $this->doGetDefinitionKeys($LCID, $pathParts, array());
		if ($this->hasI18nSynchro())
		{
			$synchro = $this->getI18nSynchro();
			if (isset($synchro[$LCID]))
			{
				foreach ($synchro[$LCID] as $referenceLCID)
				{
					$keys = $this->doGetDefinitionKeys($referenceLCID, $pathParts, $keys);
				}
			}
		}
		return array_values($keys);
	}

	/**
	 * @param string $LCID
	 * @param string[] $pathParts
	 * @param \Change\I18n\DefinitionKey[] $keys
	 * @return \Change\I18n\DefinitionKey[]
	 */
	protected function doGetDefinitionKeys($LCID, $pathParts, $keys)
	{
		$defKeys = $this->getDefinitionCollection($LCID, $pathParts);
		if ($defKeys)
		{
			$defKeys->load();
			foreach ($defKeys->getDefinitionKeys() as $defKey)
			{
				if (!isset($keys[$defKey->getId()]))
				{
					$keys[$defKey->getId()] = $defKey;
				}
			}
			foreach (array_reverse($defKeys->getIncludesPaths()) as $includePath)
			{
				$includeParts = explode('.', $includePath);
				$keys = $this->doGetDefinitionKeys($LCID, $includeParts, $keys);
			}
		}
		return $keys;
	}

	/**
	 * @param string $LCID
	 * @param string[] $pathParts
	 * @return \Change\I18n\DefinitionCollection | null
	 */
	public function getDefinitionCollection($LCID, $pathParts)
	{
		$collectionPath = $this->getCollectionPath($pathParts);
		if ($collectionPath === null)
		{
			return null;
		}
		if (!file_exists($collectionPath))
		{
			return null;
		}

		$code = implode('.', $pathParts) . '-' . $LCID;
		if (!array_key_exists($collectionPath, $this->definitionCollections))
		{
			$this->definitionCollections[$code] = new \Change\I18n\DefinitionCollection($LCID, $collectionPath);
		}
		return $this->definitionCollections[$code];
	}

	/**
	 * @param string[]
	 * @return string
	 */
	protected function getCollectionPath($pathParts)
	{
		if ($this->packageList === null)
		{
			$workspace = $this->getWorkspace();
			$this->packageList = array();

			// Core.
			$this->packageList['c'] = $this->workspace->changePath('I18n', 'Assets');

			// Modules.
			if (is_dir($workspace->pluginsModulesPath()))
			{
				$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->pluginsModulesPath(), '*', '*', 'I18n', 'Assets'));
				foreach (\Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT) as
						 $path)
				{
					$parts = explode(DIRECTORY_SEPARATOR, $path);
					$count = count($parts);
					$this->packageList[strtolower('m.' . $parts[$count - 4] . '.' . $parts[$count - 3])] = $path;
				}
			}
			if (is_dir($workspace->projectModulesPath()))
			{
				$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->projectModulesPath(), '*', 'I18n', 'Assets'));
				foreach (\Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT) as
						 $path)
				{
					$parts = explode(DIRECTORY_SEPARATOR, $path);
					$count = count($parts);
					$this->packageList[strtolower('m.project.' . $parts[$count - 3])] = $path;
				}
			}
			// Themes.
			if (is_dir($workspace->pluginsThemesPath()))
			{
				$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->pluginsThemesPath(), '*', '*', 'I18n', 'Assets'));
				foreach (\Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT) as
						 $path)
				{
					$parts = explode(DIRECTORY_SEPARATOR, $path);
					$count = count($parts);
					$this->packageList[strtolower('t.' . $parts[$count - 4] . '.' . $parts[$count - 3])] = $path;
				}
			}
		}

		$collectionPath = null;
		switch ($pathParts[0])
		{
			case 'c':
				$collectionPath =
					$this->packageList['c'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 1));
				break;
			case 'm':
			case 't':
				if (isset($this->packageList[$pathParts[0] . '.' . $pathParts[1] . '.' . $pathParts[2]]))
				{
					$packagePath = $this->packageList[$pathParts[0] . '.' . $pathParts[1] . '.' . $pathParts[2]];
					$collectionPath =
						$packagePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 3));
				}
				break;
			case 'default':
				break;
		}
		return $collectionPath;
	}

	/**
	 * For example: formatText('fr_FR', 'My text.')
	 * @api
	 * @param string $LCID
	 * @param string $text
	 * @param string $format 'TEXT' or 'HTML'
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 * @return string
	 */
	public function formatText($LCID, $text, $format = DefinitionKey::TEXT, $formatters = array(), $replacements = array())
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
			$text = $this->dispatchFormatting($text, $format, $formatters, $LCID);
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
		return $this->configuration->getEntry('Change/Events/I18n', array());
	}

	/**
	 * @param EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
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
		$event = new \Zend\EventManager\Event(static::EVENT_KEY_NOT_FOUND, $this, $args);
		$callback = function ($result)
		{
			return is_string($result);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && is_string($results->last())) ? $results->last() : $event->getParam('text');
	}

	/**
	 * @param \Zend\EventManager\Event $event
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
	 * @param string $textFormat
	 * @param string[] $formatters
	 * @param string $LCID
	 * @return string
	 */
	protected function dispatchFormatting($text, $textFormat, $formatters, $LCID)
	{
		$args = array('text' => $text, 'textFormat' => $textFormat, 'formatters' => $formatters, 'LCID' => $LCID);
		$event = new \Zend\EventManager\Event(static::EVENT_FORMATTING, $this, $args);
		$callback = function ($result)
		{
			return is_string($result);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && is_string($results->last())) ? $results->last() : $event->getParam('text');
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onFormatting($event)
	{
		$text = $event->getParam('text');
		$format = $event->getParam('textFormat');
		$LCID = $event->getParam('LCID');
		foreach ($event->getParam('formatters') as $formatter)
		{
			if ($formatter === 'raw' || $formatter === $this->ignoreTransform[$format])
			{
				continue;
			}
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
		return $this->transForLCID($LCID, 'c.date.default-date-format');
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
		return $this->transForLCID($LCID, 'c.date.default-datetime-format');
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
	 * Format a date. The format parameter
	 * @api
	 * @param string $LCID
	 * @param \DateTime $gmtDate
	 * @param string $format
	 * @param \DateTimeZone $timeZone
	 * @return string
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
}
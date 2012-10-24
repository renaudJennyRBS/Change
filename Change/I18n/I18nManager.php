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
	 * @var string[] two lower-cased letters codes, ex: "fr"
	 */
	protected $m_workLang = array();
		
	/**
	 * @var string two lower-cased letters code, ex: "fr"
	 */
	protected $m_ui_lang;
	
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
		
		$this->m_supportedLanguages = explode(',', $config->getEntry('i18n/supported-languages', 'fr'));
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
	public function getUILang()
	{
		if ($this->m_ui_lang === null)
		{
			$ctrl = \Change\Application::getInstance()->getController();
			$uilang = $ctrl ? $ctrl->getStorage()->readForUser('uilang') : null;
			$this->setUILang($uilang ? $uilang : $this->getDefaultLang());
		}
		return $this->m_ui_lang;
	}
	
	/**
	 * Set the interface language code.
	 * @api
	 * @throws \InvalidArgumentException if the lang is not supported
	 * @param string $lang two lower-cased letters code, ex: "fr"
	 */
	public function setUILang($lang)
	{
		if (!in_array($lang, $this->getSupportedLanguages()))
		{
			throw new \InvalidArgumentException('Not supported language: ' . $lang);
		}
		$this->m_ui_lang = $lang;
	}
	
	/**
	 * Get the current language code.
	 * @api
	 * @return string two lower-cased letters code, ex: "fr"
	 */
	public function getLang()
	{
		if (count($this->m_workLang) > 0)
		{
			return end($this->m_workLang);
		}
		else
		{
			return $this->getUILang();
		}
	}
	
	/**
	 * Push a new working language code.
	 * @api
	 * @throws \InvalidArgumentException
	 * @param string $lang two lower-cased letters code, ex: "fr"
	 */
	public function pushLang($lang)
	{
		if (!in_array($lang, $this->getSupportedLanguages()))
		{
			throw new \InvalidArgumentException('Not supported language: ' . $lang);
		}
		array_push($this->m_workLang, $lang);
	}
	
	/**
	 * Pop the last working language code.
	 * @api
	 * @throws \LogicException if there is no lang to pop
	 * @throws \Exception if provided
	 * @param \Exception $exception
	 */
	public function popLang($exception = null)
	{
		// FIXME: if the exception was raized by pushLang (and so no lang was pushed)?
		if ($this->getLangStackSize() === 0)
		{
			throw new \LogicException('No language to pop.');
		}
		array_pop($this->m_workLang);
		if ($exception !== null)
		{
			throw $exception;
		}
	}
	
	/**
	 * Get the lang stack size.
	 * @api
	 * @return integer
	 */
	public function getLangStackSize()
	{
		return count($this->m_workLang);
	}
	
	/**
	 * Loads the i18n synchro configuration. 
	 */
	protected function loadI18nSynchroConfiguration()
	{
		$data = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n/synchro/documents', null);
		$this->m_i18n_documents_synchro = $this->cleanI18nSynchroConfiguration($data);
		$data = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n/synchro/keys', null);
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
					$fromLangs = array();
					foreach (array_map('trim', explode(',', $froms)) as $fromLang)
					{
						if (in_array($fromLang, $langs))
						{
							$fromLangs[] = $fromLang;
						}
					}						
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
	 * @param integer $documentId
	 */
	public function resetSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			$d = \DocumentHelper::getDocumentInstanceIfExists($documentId); //TODO Old class Usage
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				$this->dbProvider->setI18nSynchroStatus($d->getId(), $d->getLang(), self::SYNCHRO_MODIFIED, null);
			}
		}
	}
	
	/**
	 * @param integer $documentId
	 */
	public function initSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			$d = \DocumentHelper::getDocumentInstanceIfExists($documentId); //TODO Old class Usage
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				foreach ($d->getI18nInfo()->getLangs() as $lang)
				{
					$this->dbProvider->setI18nSynchroStatus($d->getId(), $lang, self::SYNCHRO_MODIFIED, null);
				}
			}
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function getDocumentIdsToSynchronize()
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			return $this->dbProvider->getI18nSynchroIds();
		}
		return array();
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 *		- isLocalized : boolean
	 *		- action : 'none'|'generate'|'synchronize'
	 *		- config : array
	 *			- 'fr'|'??' : string[]
	 *			- ...
	 *		- states : array
	 *			- 'fr'|'??' : array
	 *				- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 *				- from : fr'|'en'|'??'|null
	 *			- ...
	 */
	public function getI18nSynchroForDocument($document)
	{
		$result = array('isLocalized' => false, 'action' => 'none', 'config' => array());
		$pm = $document->getPersistentModel();
		if ($pm->isLocalized())
		{
			$result['isLocalized'] = true;
			if ($this->hasI18nDocumentsSynchro())
			{
				$result['config'] = $this->getI18nDocumentsSynchro();
				$data = $this->dbProvider->getI18nSynchroStatus($document->getId());
				$result['states'] = $data;
				foreach ($document->getI18nInfo()->getLangs() as $lang)
				{
					if (!isset($data[$lang]))
					{
						$result['action'] = 'generate';
						break;
					}
					elseif ($data[$lang]['status'] === self::SYNCHRO_MODIFIED)
					{
						$result['action'] = 'synchronize';
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public function synchronizeDocumentId($documentId)
	{
		if (!$this->hasI18nDocumentsSynchro())
		{
			// No synchro configured.
			return false;
		}
		$d = \DocumentHelper::getDocumentInstanceIfExists($documentId); //TODO Old class Usage
		if ($d === null)
		{
			// Invalid document.
			return false;
		}
	
		$pm = $d->getPersistentModel();
		if (!$pm->isLocalized())
		{
			// Not applicable on this document.
			return false;
		}
	
		try
		{
			$this->dbProvider->beginTransaction();
			$ds = $d->getDocumentService();
	
			$synchroConfig = $ds->getI18nSynchroConfig($d, $this->getI18nDocumentsSynchro());
			if (count($synchroConfig))
			{
				//TODO Old class Usage
				$dcs = \f_DataCacheService::getInstance();
				$datas = $this->dbProvider->getI18nSynchroStatus($d->getId());
				if (count($datas) === 0)
				{
					foreach ($d->getI18nInfo()->getLangs() as $lang)
					{
						$datas[$lang] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
					}
				}
				else
				{
					$datas[$d->getLang()] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
				}
	
				foreach ($synchroConfig as $lang => $fromLangs)
				{
					if (!isset($datas[$lang]) || $datas[$lang]['status'] === self::SYNCHRO_SYNCHRONIZED)
					{
						foreach ($fromLangs as $fromLang)
						{
							if (isset($datas[$fromLang]) && $datas[$fromLang]['status'] !== self::SYNCHRO_SYNCHRONIZED)
							{
								list($from, $to) = $this->dbProvider->prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
								try
								{
									$this->pushLang($fromLang);
	
									if ($ds->synchronizeI18nProperties($d, $from, $to))
									{
										$this->dbProvider->setI18nSynchro($pm, $to);
										$this->dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_SYNCHRONIZED, $fromLang);
										//TODO Old class Usage
										$dcs->clearCacheByPattern(\f_DataCachePatternHelper::getModelPattern($d->getDocumentModelName()));
										$dcs->clearCacheByDocId(\f_DataCachePatternHelper::getIdPattern($documentId));
									}
									elseif (isset($datas[$lang]))
									{
										$this->dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
									}
	
									$this->popLang();
								}
								catch (\Exception $e)
								{
									$this->popLang($e);
								}
								break;
							}
						}
					}
				}
	
				foreach ($datas as $lang => $synchroInfos)
				{
					if ($synchroInfos['status'] === self::SYNCHRO_MODIFIED)
					{
						$this->dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
					elseif ($synchroInfos['status'] === self::SYNCHRO_SYNCHRONIZED && !isset($synchroConfig[$lang]))
					{
						$this->dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
				}
			}
			else
			{
				$this->dbProvider->deleteI18nSynchroStatus($documentId);
			}
			$this->dbProvider->commit();
		}
		catch (\Exception $e)
		{
			$this->dbProvider->rollback($e);
			return false;
		}
		return true;
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
			$this->LCID_BY_LANG = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n/lcids', array());
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
			$this->LCID_BY_LANG = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n/lcids', array());
		}
		
		$code = array_search($lcid, $this->LCID_BY_LANG);
		if ($code === false)
		{
			return substr($lcid, 0, 2);
		}
		return $code;
	}
		
	/**
	 * For example: transData('f.boolean.true')
	 * @api
	 * @param string | \Change\I18n\PreparedKey $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, html, attr
	 * @param array $replacements
	 * @return string | $cleanKey
	 */
	public function transData($cleanKey, $formatters = array(), $replacements = array())
	{
		return $this->formatKey($this->getLang(), $cleanKey, $formatters, $replacements);
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
		return $this->formatKey($this->getUILang(), $cleanKey, $formatters, $replacements);
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
			if ($content === null)
			{
				$this->logKeyNotFound($preparedKey->getKey(), $lcid);
				return $preparedKey->getKey();
			}
		}
		else
		{
			$content = $preparedKey->getKey();
			$format = 'TEXT';
		}
		
		if ($preparedKey->hasReplacements())
		{
			$search = array();
			$replace = array();
			foreach ($preparedKey->getReplacements() as $key => $value)
			{
				$search[] = '{' . $key . '}';
				$replace[] = $value;
			}
			$content = str_replace($search, $replace, $content);
		}
		
		if ($preparedKey->hasFormatters())
		{
			foreach ($preparedKey->getFormatters() as $formatter)
			{
				if ($formatter === 'raw' || $formatter === $this->ignoreTransform[$format])
				{
					continue;
				}
				if (isset($this->transformers[$formatter]))
				{
					$content = $this->{$this->transformers[$formatter]}($content, $lang);
				}
				else
				{
					\Change\Application::getInstance()->getApplicationServices()->getLogging()->warn(__METHOD__ . ' Invalid formatter ' . $formatter);
				}
			}
		}
		return $content;
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
				$lang = ($infos[1] === 'transdata') ? $this->getLang() : $this->getUILang();
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
	 * @return array [baseKey => nbLocales]
	 */
	public function getPackageNames()
	{
		return $this->dbProvider->getPackageNames();
	}
	
	/**
	 * @return array [baseKey => nbLocales]
	 */
	public function getUserEditedPackageNames()
	{
		return $this->dbProvider->getUserEditedPackageNames();
	}
	
	/**
	 *
	 * @param string $keyPath
	 * @return array[id => [lcid => ['content' => string, 'useredited' => integer, 'format' => string]]]
	 */
	public function getPackageContent($keyPath)
	{
		$result = $this->dbProvider->getPackageData($keyPath);
		$contents = array();
		foreach ($result as $row)
		{
			$contents[$row['id']][$row['lang']] = array('content' => $row['content'],
				'useredited' => $row['useredited'] == "1", 'format' => $row['format']);
		}
		return $contents;
	}
	
	/**
	 * @param string $lcid exemple fr_FR
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param string $format TEXT | HTML
	 */
	public function updateUserEditedKey($lcid, $id, $keyPath, $content, $format)
	{
		$this->updateKey($lcid, $id, $keyPath, $content, $format, true);
	}
	
	/**
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 */
	public function deleteUserEditedKey($lcid, $id, $keyPath)
	{
		$this->dbProvider->deleteI18nKey($keyPath, $id, $lcid);
	}
	
	/**
	 * @param string $lcid exemple fr_FR
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param string $format TEXT | HTML
	 * @param boolean $userEdited
	 */
	public function updateKey($lcid, $id, $keyPath, $content, $format, $userEdited = false)
	{
		$this->dbProvider->addTranslate($lcid, $id, $keyPath, $content, $userEdited ? 1 : 0, $format, true);
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
		return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
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
		return str_replace(array("\\", "\t", "\n", "\"", "'"),
			array("\\\\", "\\t", "\\n", "\\\"", "\\'"), $text);
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
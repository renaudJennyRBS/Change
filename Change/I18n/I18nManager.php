<?php
namespace Change\I18n;
use \Change\DB\Provider;
use \Change\Application\LoggingManager;

/**
 * @name \Change\I18n\I18nManager
 * @method \Change\I18n\I18nManager getInstance()
 */
class I18nManager extends \Change\AbstractSingleton
{
	protected $LCID_BY_LANG = null;
	protected $ignoreTransform;
	protected $transformers;
	
	/**
	 * @var array
	 */
	protected $m_workLang = array();
	
	/**
	 * @var array
	 */
	protected $m_ui_supportedLanguages = array();
	
	/**
	 * @var string (lowercase) Ex: fr
	 */
	protected $m_ui_lang;
	
	/**
	 * @var array
	 */
	protected $m_supportedLanguages = array();
	
	/**
	 * @var string (lowercase) Ex: fr
	 */
	protected $m_lang;
	
	/**
	 * @var array
	 */
	protected $m_i18n_synchro = null;
	
	
	protected function __construct()
	{
		$this->ignoreTransform = array('TEXT' => 'raw', 'HTML' => 'html');
		
		$this->transformers = array('lab' => 'transformLab', 'uc' => 'transformUc', 'ucf' => 'transformUcf', 'lc' => 'transformLc', 
			'js' => 'transformJs', 'html' => 'transformHtml', 'text' => 'transformText', 'attr' => 'transformAttr', 'space' => 'transformSpace', 
			'etc' => 'transformEtc', 'ucw' => 'transformUcw');
		
		if (defined('SUPPORTED_LANGUAGES'))
		{
			$this->m_supportedLanguages = explode(' ', strtolower(SUPPORTED_LANGUAGES));
		}
		else
		{
			$this->m_supportedLanguages = array('fr');
		}
		
		if (defined('UI_SUPPORTED_LANGUAGES'))
		{
			$this->m_ui_supportedLanguages = explode(' ', strtolower(UI_SUPPORTED_LANGUAGES));
		}
		else
		{
			$this->m_ui_supportedLanguages = array('fr');
		}
		
		$this->m_lang = $this->getDefaultLang();
	}
	
	/**
	 * @return array
	 */
	public function getSupportedLanguages()
	{
		return $this->m_supportedLanguages;
	}
	
	/**
	 * @return array
	 */
	public function getUISupportedLanguages()
	{
		return $this->m_ui_supportedLanguages;
	}
	
	/**
	 * @return string
	 */
	public function getDefaultLang()
	{
		return $this->m_supportedLanguages[0];
	}
	
	/**
	 * @return string
	 */
	public function getUIDefaultLang()
	{
		return $this->m_ui_supportedLanguages[0];
	}
	
	/**
	 * @return string current language to lower case Ex: fr
	 */
	public function getLang()
	{
		if (count($this->m_workLang) > 0)
		{
			return end($this->m_workLang);
		}
		else
		{
			return $this->m_lang;
		}
	}
	
	/**
	 * @exception BadInitializationException if the current UI language is not defined
	 * @return string current UI language to lower case Ex: fr
	 */
	public function getUILang()
	{
		if ($this->m_ui_lang === null)
		{
			$ctrl = \Change\Application::getInstance()->getController();
			$uilang = $ctrl ? $ctrl->getStorage()->readForUser('uilang') : null;
			$this->m_ui_lang = $uilang ? $uilang : $this->getUIDefaultLang();
		}
		return $this->m_ui_lang;
	}
	
	/**
	 * @exception RuntimeException if the lang is already defined to other language
	 * @param string $lang
	 * @return boolean
	 */
	public function setLang($lang)
	{
		if (count($this->m_workLang) > 0)
		{
			throw new \RuntimeException("The current language is already defined to :" . $this->m_lang);
		}
		
		if (in_array($lang, $this->getSupportedLanguages()))
		{
			$this->m_lang = $lang;
			return true;
		}
		return false;
	}
	
	/**
	 * @exception IllegalArgumentException if the UI lang is not supported
	 * @param string $lang
	 */
	public function setUILang($lang)
	{
		if (in_array($lang, $this->getUISupportedLanguages()))
		{
			$this->m_ui_lang = $lang;
		}
		else
		{
			throw new \IllegalArgumentException('Invalid UI supported lang :' . $lang);
		}
	}
	public function pushLang($lang)
	{
		if ($lang === null || !in_array($lang, $this->m_supportedLanguages))
		{
			throw new \IllegalArgumentException('Invalid supported lang :' . $lang);
		}
		array_push($this->m_workLang, $lang);
	}
	
	/**
	 * @param Exception $exception
	 * @throws $exception if provided
	 */
	public function popLang($exception = null)
	{
		array_pop($this->m_workLang);
		if ($exception !== null)
		{
			throw $exception;
		}
	}
	protected function loadI18nSynchroConfiguration()
	{
		$this->m_i18n_synchro = false;
		$data = \Change\Application::getInstance()->getConfiguration()->getEntry('i18nsynchro', null);
		
		if (is_array($data) && count($data))
		{
			$langs = $this->getSupportedLanguages();
			$result = array();
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
				$this->m_i18n_synchro = $result;
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	public function hasI18nSynchro()
	{
		if ($this->m_i18n_synchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->m_i18n_synchro !== false;
	}
	
	/**
	 * @return array string : string[]
	 */
	public function getI18nSynchro()
	{
		return $this->hasI18nSynchro() ? $this->m_i18n_synchro : array();
	}
	
	const SYNCHRO_MODIFIED = 'MODIFIED';
	const SYNCHRO_VALID = 'VALID';
	const SYNCHRO_SYNCHRONIZED = 'SYNCHRONIZED';
	
	/**
	 * @param integer $documentId
	 */
	public function resetSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nSynchro())
		{
			$d = \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				Provider::getInstance()->setI18nSynchroStatus($d->getId(), $d->getLang(), self::SYNCHRO_MODIFIED, null);
			}
		}
	}
	
	
	/**
	 * @param integer $documentId
	 */
	public function initSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nSynchro())
		{
			$d = \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				foreach ($d->getI18nInfo()->getLangs() as $lang)
				{
					Provider::getInstance()->setI18nSynchroStatus($d->getId(), $lang, self::SYNCHRO_MODIFIED, null);
				}
			}
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function getDocumentIdsToSynchronize()
	{
		if ($this->hasI18nSynchro())
		{
			return Provider::getInstance()->getI18nSynchroIds();
		}
		return array();
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 * 		- isLocalized : boolean
	 * 		- action : 'none'|'generate'|'synchronize'
	 * 		- config : array
	 * 			- 'fr'|'??' : string[]
	 * 			- ...
	 *		- states : array
	 * 			- 'fr'|'??' : array
	 * 				- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * 				- from : fr'|'en'|'??'|null
	 * 			- ...
	 */
	public function getI18nSynchroForDocument($document)
	{
		$result = array('isLocalized' => false, 'action' => 'none', 'config' => array());
		$pm = $document->getPersistentModel();
		if ($pm->isLocalized())
		{
			$result['isLocalized'] = true;
			if ($this->hasI18nSynchro())
			{
				$result['config'] = $this->getI18nSynchro();
				$data = Provider::getInstance()->getI18nSynchroStatus($document->getId());
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
		if (!$this->hasI18nSynchro())
		{
			//No synchro configured
			return false;
		}
		$d = \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($documentId);
		if ($d === null)
		{
			//Invalid document
			return false;
		}
	
		$pm = $d->getPersistentModel();
		if (!$pm->isLocalized())
		{
			//Not applicable on this document
			return false;
		}
	
		$dbp = Provider::getInstance();
		try
		{
			$dbp->beginTransaction();
			$ds = $d->getDocumentService();
	
			$synchroConfig = $ds->getI18nSynchroConfig($d, $this->getI18nSynchro());
			if (count($synchroConfig))
			{
				//TODO Old class Usage
				$dcs = \f_DataCacheService::getInstance();
				$datas = $dbp->getI18nSynchroStatus($d->getId());
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
								list($from, $to) = $dbp->prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
								try
								{
									$this->pushLang($fromLang);
	
									if ($ds->synchronizeI18nProperties($d, $from, $to))
									{
										$dbp->setI18nSynchro($pm, $to);
										$dbp->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_SYNCHRONIZED, $fromLang);
										//TODO Old class Usage
										$dcs->clearCacheByPattern(\f_DataCachePatternHelper::getModelPattern($d->getDocumentModelName()));
										$dcs->clearCacheByDocId(\f_DataCachePatternHelper::getIdPattern($documentId));
									}
									elseif (isset($datas[$lang]))
									{
										Provider::getInstance()->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
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
						$dbp->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
					elseif ($synchroInfos['status'] === self::SYNCHRO_SYNCHRONIZED && !isset($synchroConfig[$lang]))
					{
						$dbp->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
				}
			}
			else
			{
				$dbp->deleteI18nSynchroStatus($documentId);
			}
			$dbp->commit();
		}
		catch (\Exception $e)
		{
			$dbp->rollback($e);
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
	 * @param string $langCode
	 * @return string
	 */
	public function getLCID($langCode)
	{
		if ($this->LCID_BY_LANG === null)
		{
			$this->LCID_BY_LANG = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n', array());
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
	 * @param string $lcid
	 * @return string
	 */
	public function getCode($lcid)
	{
		if ($this->LCID_BY_LANG === null)
		{
			$this->LCID_BY_LANG = \Change\Application::getInstance()->getConfiguration()->getEntry('i18n', array());
		}
		
		$code = array_search($lcid, $this->LCID_BY_LANG);
		if ($code === false)
		{
			return substr($lcid, 0, 2);
		}
		return $code;
	}
	
	/**
	 * @param string $cleanKey
	 * @return array(keyPath, id) || array(false, false);
	 */
	public function explodeKey($cleanKey)
	{
		$parts = explode('.', strtolower($cleanKey));
		if (count($parts) < 3)
		{
			return array(false, false);
		}
		
		$id = end($parts);
		$keyPathParts = array_slice($parts, 0, -1);
		switch ($keyPathParts[0])
		{
			case 'f' :
			case 'm' :
			case 't' :
				break;
			case 'framework' :
				$keyPathParts[0] = 'f';
				break;
			case 'modules' :
				$keyPathParts[0] = 'm';
				break;
			case 'themes' :
				$keyPathParts[0] = 't';
				break;
			default :
				return array(false, false);
		}
		return array(implode('.', $keyPathParts), $id);
	}
	
	/**
	 * @param string $string
	 * @return boolean
	 */
	public function isKey($string)
	{
		list ($path, ) = $this->explodeKey($string);
		return $path !== false;
	}
	
	/**
	 * @param string $lang
	 * @param string $cleanKey
	 * @return string | null
	 */
	public function getFullKeyContent($lang, $cleanKey)
	{
		list ($keyPath, $id) = $this->explodeKey($cleanKey);
		if ($keyPath !== false)
		{
			$lcid = $this->getLCID($lang);
			list ($content, ) = Provider::getInstance()->translate($lcid, $id, $keyPath);
			
			if ($content === null)
			{
				$this->logKeyNotFound($keyPath . '.' . $id, $lcid);
			}
			return $content;
		}
		LoggingManager::getInstance()->warn('Invalid Key ' . $cleanKey);
		return null;
	}
	
	/**
	 * For example: transData('f.boolean.true')
	 * @param string $cleanKey
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
	 * @param string $cleanKey
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
	 * @param string $lang
	 * @param string $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatKey($lang, $cleanKey, $formatters = array(), $replacements = array())
	{
		list ($keyPath, $id) = $this->explodeKey($cleanKey);
		if ($keyPath !== false)
		{
			$lcid = $this->getLCID($lang);
			list ($content, $format) = Provider::getInstance()->translate($lcid, $id, $keyPath);
			if ($content === null)
			{
				$this->logKeyNotFound($keyPath . '.' . $id, $lcid);
				return $cleanKey;
			}
		}
		else
		{
			$content = $cleanKey;
			$format = 'TEXT';
		}
		
		if (count($replacements))
		{
			$search = array();
			$replace = array();
			foreach ($replacements as $key => $value)
			{
				$search[] = '{' . $key . '}';
				$replace[] = $value;
			}
			$content = str_replace($search, $replace, $content);
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
					$content = $this->{$this->transformers[$formatter]}($content, $lang);
				}
				else
				{
					LoggingManager::getInstance()->warn(__METHOD__ . ' Invalid formatter ' . $formatter);
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
				list ($key, $formatters, $replacements) = $this->parseTransString($infos[2]);
				$replace[] = $this->formatKey($lang, $key, $formatters, $replacements);
			}
			$text = str_replace($search, $replace, $text);
		}
		return $text;
	}
	
	/**
	 * @param string $transString
	 * @return array[$key, $formatters, $replacements]
	 */
	public function parseTransString($transString)
	{
		$formatters = array();
		$replacements = array();
		$key = null;
		$parts = explode(',', $transString);
		$key = strtolower(trim($parts[0]));
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
					$l = strlen($value);
					if ($l === 0)
					{
						$replacements[$name] = '';
					}
					else
					{
						$replacements[$name] = $value;
					}
				}
			}
			else
			{
				$data = strtolower($data);
				$formatters[] = $data;
			}
		}
		return array($key, $formatters, $replacements);
	}
	
	/**
	 * @return array [baseKey => nbLocales]
	 */
	public function getPackageNames()
	{
		return Provider::getInstance()->getPackageNames();
	}
	
	/**
	 * @return array [baseKey => nbLocales]
	 */
	public function getUserEditedPackageNames()
	{
		return Provider::getInstance()->getUserEditedPackageNames();
	}
	
	/**
	 *
	 * @param string $keyPath
	 * @return array[id => [lcid => ['content' => string, 'useredited' => integer, 'format' => string]]]
	 */
	public function getPackageContent($keyPath)
	{
		$result = Provider::getInstance()->getPackageData($keyPath);
		$contents = array();
		foreach ($result as $row)
		{
			$contents[$row['id']][$row['lang']] = array('content' => $row['content'],
				'useredited' => $row['useredited'] == "1", 'format' => $row['format']);
		}
		return $contents;
	}
	
	protected function applyEntitiesI18nSynchro(&$entities)
	{
		$syncConf = $this->getI18nSynchro();
		if (count($syncConf) === 0) {return;}
		foreach ($syncConf as $to => $froms)
		{
			$toLCID = $this->getLCID($to);
			foreach ($froms as $from)
			{
				$fromLCID = $this->getLCID($from);
				if (isset($entities[$fromLCID]))
				{
					if (!isset($entities[$toLCID]))
					{
						$entities[$toLCID] = array();
					}
					foreach ($entities[$fromLCID] as $id => $data)
					{
						if (!isset($entities[$toLCID][$id]))
						{
							$entities[$toLCID][$id] = $data;
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param string $keyPath
	 * @param array $entities
	 */
	protected function processDatabase($keyPath, $entities)
	{
		$keyPath = strtolower($keyPath);	
		$provider = Provider::getInstance();
		$lcids = array();
		foreach ($this->getSupportedLanguages() as $lang)
		{
			$lcids[$this->getLCID($lang)] = $lang;
		}
		foreach ($entities as $lcid => $infos)
		{
			if (! isset($lcids[$lcid]))
			{
				continue;
			}
			foreach ($infos as $id => $entityInfos)
			{
				list($content, $format) = $entityInfos;
				$provider->addTranslate($lcid, strtolower($id), $keyPath, $content, 0, $format, false);
			}
		}
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
	
	public function deleteUserEditedKey($lcid, $id, $keyPath)
	{
		Provider::getInstance()->deleteI18nKey($keyPath, $id, $lcid);
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
		Provider::getInstance()->addTranslate($lcid, $id, $keyPath, $content, $userEdited ? 1 : 0, $format, true);
	}
	

	/**
	 * @param string $key
	 * @param string $lang
	 */
	protected function logKeyNotFound($key, $lang)
	{
		if (\Change\Application::getInstance()->inDevelopmentMode())
		{
			$stringLine = $lang . '/' . $key;
			LoggingManager::getInstance()->namedLog($stringLine, 'keynotfound');
		}
	}
	
	public function transformLab($text, $lang)
	{
		return $text . ($lang == 'fr' ? ' :' : ':');
	}
	
	public function transformUc($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_StringUtils::toUpper($text);
	}
	
	public function transformUcf($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_StringUtils::ucfirst($text);
	}
	
	public function transformUcw($text, $lang)
	{
		return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
	}
	
	public function transformLc($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_StringUtils::toLower($text);
	}
	
	public function transformJs($text, $lang)
	{
		return str_replace(array("\\", "\t", "\n", "\"", "'"),
			array("\\\\", "\\t", "\\n", "\\\"", "\\'"), $text);
	}
	
	public function transformHtml($text, $lang)
	{
		return nl2br(htmlspecialchars($text, ENT_COMPAT, 'UTF-8'));
	}
	
	public function transformText($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::htmlToText($text);
	}
	
	public function transformAttr($text, $lang)
	{
		//TODO Old class Usage
		return \f_util_HtmlUtils::textToAttribute($text);
	}
	
	public function transformSpace($text, $lang)
	{
		return ' ' . $text . ' ';
	}
	
	public function transformEtc($text, $lang)
	{
		return $text . '...';
	}
}
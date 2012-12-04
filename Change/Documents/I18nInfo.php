<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\I18nInfo
 */
class I18nInfo
{
	/**
	 * @var string For example: "fr"
	 */
	private $vo;

	/**
	 * @var array For example: array("fr" => "fr label")
	 */
	private $labels = array();
	
	/**
	 * @param string $vo
	 * @param string $label
	 */
	public function __construct($vo = null, $label = null)
	{
		if ($vo !== null) 
		{
			$this->vo = $vo;
			$this->labels[$vo] = $label;
		}
	}

	/**
	 * @param string $lang
	 */
	public function setVo($lang)
	{
		$this->vo = $lang;
	}

	/**
	 * @return string
	 */
	public function getVo()
	{
		return $this->vo;
	}

	/**
	 * @param string $lang
	 * @param string $label
	 */
	public function setLabel($lang, $label)
	{
		$this->labels[$lang] = $label;
	}

	/**
	 * @param string $lang
	 */
	public function removeLabel($lang)
	{
		if ($this->hasLabel($lang))
		{
			unset($this->labels[$lang]);
		}
	}

	/**
	 * @return string|null
	 */
	public function getVoLabel()
	{
		if (is_null($this->vo) || !$this->hasLabel($this->vo))
		{
			return null;
		}
		return $this->labels[$this->vo];
	}

	/**
	 * @param string $label
	 */
	public function setVoLabel($label)
	{
		$this->setLabel($this->vo, $label);
	}

	/**
	 * @return string|null
	 */
	public function getLabel()
	{
		return $this->getLabelByLang(\Change\Application::getInstance()->getApplicationServices()->getI18nManager()->getLang());
	}

	/**
	 * @return string[]
	 */
	public function getLabels()
	{
		return $this->labels;
	}

	/**
	 * @return boolean
	 */
	public function isContextLangAvailable()
	{
		$contextLang = \Change\Application::getInstance()->getApplicationServices()->getI18nManager()->getLang();
		return ($contextLang == $this->vo) || ($this->hasLabel($contextLang));
	}

	/**
	 * @param string $lang
	 * @return boolean
	 */
	public function isLangAvailable($lang)
	{
		return ($lang == $this->vo) || ($this->hasLabel($lang));
	}

	/**
	 * @param string $lang
	 * @return string|null
	 */
	private function getLabelByLang($lang)
	{
		if ($this->hasLabel($lang))
		{
			return $this->labels[$lang];
		}
		return $this->getVoLabel();
	}

	/**
	 * @param string $lang
	 * @return boolean
	 */
	private function hasLabel($lang)
	{
		if (is_null($this->labels) || !array_key_exists($lang, $this->labels))
		{
			return false;
		}
		return true;
	}

	/**
	 * @return multitype:string |multitype:
	 */
	public function getLangs()
	{
		$langs = array($this->vo);
		if (count($this->labels) == 0)
		{
			return $langs;
		}
		else
		{
			return array_unique(array_merge($langs, array_keys($this->labels)));
		}
	}
	
	/**
	 * @return array
	 */
	public function toPersistentProviderArray()
	{
		$array = array('lang_vo' => $this->vo);
		foreach ($this->labels as $lang => $value) 
		{
			if ($value !== null)
			{
				$array['label_' . $lang] = $value;
			}
		}
		return $array;
	}
	
	/**
	 * Return NULL si $i18nInfos ne contient pas d'information de localisation
	 * @param array $i18nInfos
	 * @return \Change\Documents\I18nInfo
	 */
	public static function getInstanceFromArray($i18nInfos)
	{
		$instance = new I18nInfo();		
		foreach ($i18nInfos as $key => $value) 
		{
			if ($key === 'lang_vo')
			{
				$instance->vo = $value;
			} 
			elseif (strpos($key, 'label_') === 0 && strlen($key) === 8 && !is_null($value))
			{
				$instance->labels[substr($key, 6)] = $value;
			}	
		}
		
		if ($instance->vo === null)
		{
			return null; 	
		}
		return $instance;
	}
}
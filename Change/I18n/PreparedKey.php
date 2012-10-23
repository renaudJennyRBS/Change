<?php
namespace Change\I18n;

/**
 * @name \Change\I18n\PreparedKey
 */
class PreparedKey
{
	/**
	 * @var string[]
	 */
	protected $keyParts;
	
	/**
	 * @var string[]
	 */
	protected $formatters;
	
	/**
	 * @var array<string => string>
	 */
	protected $replacements;
	
	/**
	 * @param string $key
	 * @param string[] $formatters
	 * @param array<string => string> $replacements
	 */
	public function __construct($key, $formatters = array(), $replacements = array())
	{
		$this->setKey($key);
		$this->formatters = $formatters;
		$this->replacements = $replacements;
	}
	
	/**
	 * @return string
	 */
	public function getKey()
	{
		return implode('.', $this->keyParts);
	}
	
	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->keyParts = explode('.', strtolower(trim($key)));
		switch ($this->keyParts[0])
		{
			case 'framework' :
				$this->keyParts[0] = 'f';
				break;
			case 'modules' :
				$this->keyParts[0] = 'm';
				break;
			case 'themes' :
				$this->keyParts[0] = 't';
				break;
		}
	}
	
	/**
	 * @return boolean
	 */
	public function isValid()
	{
		return count($this->keyParts) >= 3 && in_array($this->keyParts[0], array('f', 'm', 't'));
	}
	
	/**
	 * @return string
	 */
	public function getPath()
	{
		if (!$this->isValid())
		{
			return false;
		}
		return implode('.', array_slice($this->keyParts, 0, -1));
	}
	
	/**
	 * @return string
	 */
	public function getId()
	{
		if (count($this->keyParts) < 3)
		{
			return false;
		}
		return end($this->keyParts);
	}
	
	/**
	 * @return boolean
	 */
	public function hasFormatters()
	{
		return count($this->formatters) > 0;
	}
	
	/**
	 * @return string[]
	 */
	public function getFormatters()
	{
		return $this->formatters;
	}
	
	/**
	 * @param string[] $formatters
	 */
	public function setFormatters($formatters)
	{
		$this->formatters = $formatters;
	}
	
	/**
	 * @param string $formatter
	 */
	public function addFormatter($formatter)
	{
		if (!in_array($formatter, $this->formatters))
		{
			$this->formatters[] = $formatter;
		}
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 */
	public function mergeFormatters($formatters)
	{
		$this->formatters = array_unique(array_merge($this->formatters, $formatters));
	}

	/**
	 * @return boolean
	 */
	public function hasReplacements()
	{
		return count($this->replacements) > 0;
	}
	
	/**
	 * @return array<string => string>
	 */
	public function getReplacements()
	{
		return $this->replacements;
	}
	
	/**
	 * @param array<string => string> $replacements
	 */
	public function setReplacements($replacements)
	{
		$this->replacements = $replacements;
	}
		
	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setReplacement($key, $value)
	{
		$this->replacements[$key] = $value;
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 */
	public function mergeReplacements($replacements)
	{
		$this->replacements = array_merge($this->replacements, $replacements);
	}
}
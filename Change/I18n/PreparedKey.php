<?php
namespace Change\I18n;

/**
 * @api
 * @name \Change\I18n\PreparedKey
 */
class PreparedKey
{
	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string[]
	 */
	protected $formatters;

	/**
	 * @var array<string => string>
	 */
	protected $replacements;

	/**
	 * @api
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
	public function getRawValue()
	{
		return $this->key;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getKey()
	{
		return $this->isValid() ? $this->path . '.' . $this->id : $this->key;
	}

	/**
	 * @api
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
		$this->path = null;
		$this->id = null;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isValid()
	{
		if ($this->path === null)
		{
			$key = \Change\Stdlib\String::toLower($this->key);
			if (preg_match('/^(c|m|t)\.[a-z0-9]+(\.[a-z0-9-]+)+$/', $key))
			{
				$parts = explode('.', $key);
				$this->path = implode('.', array_slice($parts, 0, -1));
				$this->id = end($parts);
			}
			else
			{
				$this->path = false;
				$this->id = false;
			}
		}
		return $this->path !== false;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getPath()
	{
		return $this->isValid() ? $this->path : null;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getId()
	{
		return $this->isValid() ? $this->id : null;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasFormatters()
	{
		return count($this->formatters) > 0;
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getFormatters()
	{
		return $this->formatters;
	}

	/**
	 * @api
	 * @param string[] $formatters
	 */
	public function setFormatters($formatters)
	{
		$this->formatters = $formatters;
	}

	/**
	 * @api
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
	 * @api
	 * @param string $key
	 * @param string $value
	 */
	public function mergeFormatters($formatters)
	{
		$this->formatters = array_unique(array_merge($this->formatters, $formatters));
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasReplacements()
	{
		return count($this->replacements) > 0;
	}

	/**
	 * @api
	 * @return array<string => string>
	 */
	public function getReplacements()
	{
		return $this->replacements;
	}

	/**
	 * @api
	 * @param array<string => string> $replacements
	 */
	public function setReplacements($replacements)
	{
		$this->replacements = $replacements;
	}

	/**
	 * @api
	 * @param string $key
	 * @param string $value
	 */
	public function setReplacement($key, $value)
	{
		$this->replacements[$key] = $value;
	}

	/**
	 * @api
	 * @param string $key
	 * @param string $value
	 */
	public function mergeReplacements($replacements)
	{
		$this->replacements = array_merge($this->replacements, $replacements);
	}
}
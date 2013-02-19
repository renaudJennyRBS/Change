<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\DocumentResult
 */
class DocumentResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var array
	 */
	protected $links = array();

	/**
	 * @var array
	 */
	protected $i18n = array();


	public function __construct()
	{
	}

	/**
	 * @param array $links
	 */
	public function setLinks($links)
	{
		$this->links = $links;
	}

	/**
	 * @return array
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * @param array|\Change\Http\Rest\Result\Link $link
	 */
	public function addLink($link)
	{
		$this->links[] = $link;
	}

	/**
	 * @param array $properties
	 */
	public function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}

	/**
	 * @param array $i18n
	 */
	public function setI18n($i18n)
	{
		$this->i18n = $i18n;
	}

	/**
	 * @return array
	 */
	public function getI18n()
	{
		return $this->i18n;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$properties = array();
		foreach ($this->getProperties() as $name => $value)
		{
			if (is_object($value) && is_callable(array($value, 'toArray')))
			{
				$value = $value->toArray();
			}
			elseif(is_array($value))
			{
				$value = array_map(function($item) {
					return (is_object($item) && is_callable(array($item, 'toArray'))) ? $item->toArray() : $item;
				}, $value);
			}
			$properties[$name] = $value;
		}

		$array =  array('properties' => $properties);

		if (count($this->getLinks()))
		{
			$array['links'] = array_map(function($item) {
				return ($item instanceof \Change\Http\Rest\Result\Link) ? $item->toArray() : $item;
			}, $this->getLinks());
		}

		if (count($this->getI18n()))
		{
			$array['i18n'] = $this->getI18n();
		}
		return $array;
	}
}
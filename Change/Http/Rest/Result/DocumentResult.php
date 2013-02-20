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
	 * @var Links
	 */
	protected $links;

	/**
	 * @var array
	 */
	protected $i18n = array();


	public function __construct()
	{
		$this->links = new Links();
	}

	/**
	 * @param array|\Change\Http\Rest\Result\Links $links
	 */
	public function setLinks($links)
	{
		if ($links instanceof Links)
		{
			$this->links = $links;
		}
		elseif (is_array($links))
		{
			$this->links->exchangeArray($links);
		}
	}

	/**
	 * @return \Change\Http\Rest\Result\Links
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * @param string $rel
	 * @return array|false
	 */
	public function getRelLinks($rel)
	{
		return $this->links[$rel];
	}

	/**
	 * @param \Change\Http\Rest\Result\Link|array $link
	 */
	public function addLink($link)
	{
		$this->links[] = $link;
	}

	/**
	 * @param string $rel
	 * @param string|array|\Change\Http\Rest\Result\Link $link
	 */
	public function addRelLink($rel, $link)
	{
		$this->links[$rel] = $link;
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

		$links = $this->getLinks();
		if ($links->count())
		{
			$array['links'] = $links->toArray();
		}

		if (count($this->getI18n()))
		{
			$array['i18n'] = $this->getI18n();
		}
		return $array;
	}
}
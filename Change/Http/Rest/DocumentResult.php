<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\DocumentResult
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
		$array =  array('properties' => $this->getProperties());
		if (count($this->getLinks()))
		{
			$array['links'] = $this->getLinks();
		}
		if (count($this->getI18n()))
		{
			$array['i18n'] = $this->getI18n();
		}
		return $array;
	}
}
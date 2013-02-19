<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\NamespaceResult
 */
class NamespaceResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $links = array();

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
	 * @param \Change\Http\Rest\Result\Link|array $link
	 */
	public function addLink($link)
	{
		$this->links[] = $link;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$links = array_map(function($item) {
			return ($item instanceof \Change\Http\Rest\Result\Link) ? $item->toArray() : $item;
		}, $this->getLinks());
		return array('links' => $links);
	}
}
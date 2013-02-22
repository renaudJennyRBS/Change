<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\NamespaceResult
 */
class NamespaceResult extends \Change\Http\Result
{
	/**
	 * @var \Change\Http\Rest\Result\Links
	 */
	protected $links;


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
	 * @return array
	 */
	public function toArray()
	{
		return array('links' => $this->links->toArray());
	}
}
<?php
namespace Change\Http\Rest\Result;

use Change\Http\Result;
use Change\Http\UrlManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Change\Http\Rest\Result\BlockResult
 */
class BlockResult extends Result
{
	/**
	 * @var Information
	 */
	protected $information;

	/**
	 * @var Links
	 */
	protected $links;

	/**
	 * @param UrlManager $urlManager
	 * @param Information $information
	 */
	public function __construct(UrlManager $urlManager, Information $information)
	{
		$this->information = $information;
		$this->links = new Links();
		$this->addLink(new BlockLink($urlManager, $information, false));
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
	 * @return array
	 */
	public function toArray()
	{
		$array =  array();
		$links = $this->getLinks();
		$array['links'] = $links->toArray();
		$array['properties'] = $this->information->toArray();
		return $array;
	}
}
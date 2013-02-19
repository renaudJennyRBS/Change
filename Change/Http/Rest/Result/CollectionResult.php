<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\CollectionResult
 */
class CollectionResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $links = array();

	/**
	 * @var array
	 */
	protected $resources = array();

	/**
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * @var integer
	 */
	protected $limit = 10;

	/**
	 * @var integer
	 */
	protected $count = 0;


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
	 * @param int $count
	 */
	public function setCount($count)
	{
		$this->count = $count;
	}

	/**
	 * @return int
	 */
	public function getCount()
	{
		return $this->count;
	}

	/**
	 * @param array $resources
	 */
	public function setResources($resources)
	{
		$this->resources = $resources;
	}

	/**
	 * @return array
	 */
	public function getResources()
	{
		return $this->resources;
	}

	/**
	 * @param mixed $resource
	 */
	public function addResource($resource)
	{
		$this->resources[] = $resource;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;
	}

	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}

	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$resources = array_map(function($item) {
			return (is_object($item) && is_callable(array($item, 'toArray'))) ? $item->toArray() : $item;
		}, $this->getResources());

		$array =  array('pagination' => array('count' => $this->getCount(), 'offset' => $this->getOffset(), 'limit' => $this->getLimit()),
			'resources' => $resources);

		$links = $this->getLinks();
		if(is_array($links) && count($links))
		{
			$array['links'] = array_map(function($item) {
				return ($item instanceof \Change\Http\Rest\Result\Link) ? $item->toArray() : $item;
			}, $links);
		}
		return $array;
	}
}
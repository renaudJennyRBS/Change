<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\CollectionResult
 */
class CollectionResult extends \Change\Http\Result
{
	/**
	 * @var Links
	 */
	protected $links;

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

		if($links->count())
		{
			$array['links'] = $links->toArray();
		}
		return $array;
	}
}
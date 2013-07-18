<?php
namespace Change\Http\Rest\Result;

use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\Result\Link
 */
class Link
{
	/**
	 * @var UrlManager
	 */
	protected $urlManager;

	/**
	 * @var string
	 */
	protected $rel;

	/**
	 * @var string
	 */
	protected $pathInfo;

	/**
	 * @var string|array
	 */
	protected $query;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @param UrlManager $urlManager
	 * @param null $pathInfo
	 * @param string $rel
	 */
	public function __construct(UrlManager $urlManager, $pathInfo = null, $rel = 'self')
	{
		$this->urlManager = $urlManager;
		$this->rel = $rel;
		$this->pathInfo = $pathInfo;
	}

	/**
	 * @param UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager(UrlManager $urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
	}

	/**
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @param string $pathInfo
	 * @return $this
	 */
	public function setPathInfo($pathInfo)
	{
		$this->pathInfo = $pathInfo;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPathInfo()
	{
		return $this->pathInfo;
	}

	/**
	 * @param array|string $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;
		return $this;
	}

	/**
	 * @return array|string
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * @param string $rel
	 * @return $this
	 */
	public function setRel($rel)
	{
		$this->rel = $rel;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRel()
	{
		return $this->rel;
	}

	/**
	 * @param string $method
	 * @return $this
	 */
	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function href()
	{
		return $this->urlManager->getByPathInfo($this->getPathInfo())->setQuery($this->getQuery())->normalize()->toString();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('rel' => $this->getRel(), 'href' => $this->href());
		if ($this->getMethod())
		{
			$array['method'] = $this->getMethod();
		}
		return $array;
	}
}
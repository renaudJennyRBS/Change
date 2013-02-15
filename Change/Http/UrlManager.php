<?php
namespace Change\Http;

/**
 * @name \Change\Http\UrlManager
 */
class UrlManager
{
	/**
	 * @var \Zend\Uri\Http
	 */
	protected $self;

	/**
	 * @var string
	 */
	protected $script;

	/**
	 * @param \Zend\Uri\Http $self
	 * @param string|null $script
	 */
	public function __construct(\Zend\Uri\Http $self, $script = null)
	{
		$this->self = $self;
		if (is_string($script) && $script[0] !== '/')
		{
			$script = '/'.$script;
		}
		$this->script = $script;
	}

	/**
	 * @return \Zend\Uri\Http
	 */
	public function getSelf()
	{
		return new \Zend\Uri\Http($this->self);
	}

	/**
	 * @param string|null $pathInfo
	 * @param string|null $query
	 * @param string|null $fragment
	 * @return \Zend\Uri\Http
	 */
	public function getByPathInfo($pathInfo, $query = null, $fragment = null)
	{
		$uri = $this->getSelf();
		if (is_array($pathInfo))
		{
			$pathInfo = implode('/', array_filter($pathInfo, function ($path) {return (is_string($path) && strlen($path)) || is_numeric($path);}));
		}

		if (is_string($pathInfo) && $pathInfo[0] !== '/')
		{
			$pathInfo = '/'.$pathInfo;
		}

		$uri->setPath($this->script . $pathInfo)->setQuery($query)->setFragment($fragment);
		return $uri;
	}
}

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
	 * @var string
	 */
	protected $basePath;

	/**
	 * @param \Zend\Uri\Http $self
	 * @param string|null $script
	 */
	public function __construct(\Zend\Uri\Http $self, $script = null)
	{
		$this->self = $self;
		if (is_string($script) && $script[0] !== '/')
		{
			$script = '/' . $script;
		}
		$this->script = $script;
	}

	/**
	 * @param string $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $this->normalizeBasePath($basePath);
		return $this;
	}

	/**
	 * if not empty $basePath is prefixed and suffixed by '/' if necessary.
	 * @api
	 * @param string $basePath
	 * @return null|string
	 */
	public function normalizeBasePath($basePath)
	{
		if (is_string($basePath) && isset($basePath[0]))
		{
			if ($basePath[0] !== '/')
			{
				$basePath = '/' . $basePath;
			}
			if ($basePath[strlen($basePath) - 1] !== '/')
			{
				$basePath .= '/';
			}
			return $basePath;
		}
		return null;
	}

	/**
	 * if not null BasePath are already prefixed and suffixed by '/'
	 * @api
	 * @return string|null
	 */
	public function getBasePath()
	{
		return $this->basePath;
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
			$pathInfo = implode('/', array_filter($pathInfo, function ($path)
			{
				return (is_string($path) && strlen($path)) || is_numeric($path);
			}));
		}
		if (!is_string($pathInfo))
		{
			$pathInfo = $this->basePath;
		}
		elseif (strpos($pathInfo, '/') !== 0)
		{
			$pathInfo = ($this->basePath ? $this->basePath : '/') . $pathInfo;
		}
		$uri->setPath($this->script . $pathInfo)->setQuery($query)->setFragment($fragment);
		return $uri;
	}
}

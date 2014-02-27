<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @param string $script
	 * @return \Change\Http\UrlManager
	 */
	public function __construct(\Zend\Uri\Http $self, $script = null)
	{
		$this->self = $self;
		$this->setScript($script);
	}

	/**
	 * @param string $script
	 */
	public function setScript($script)
	{
		$this->script = $this->normalizeScript($script);
	}

	/**
	 * Example: /index.php
	 * @return string|null
	 */
	public function getScript()
	{
		return $this->script;
	}

	/**
	 * if not null $script is prefixed  '/' if necessary.
	 * @api
	 * @param string $script
	 * @return null|string
	 */
	public function normalizeScript($script)
	{
		if (is_string($script) && strlen($script))
		{
			if ($script[0] !== '/')
			{
				$script = '/' . $script;
			}
			return $script;
		}
		return null;
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
	 * Example: /fr/
	 * @api
	 * @return string|null
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * if not null $basePath is prefixed and suffixed by '/' if necessary.
	 * @api
	 * @param string $basePath
	 * @return null|string
	 */
	public function normalizeBasePath($basePath)
	{
		if (is_string($basePath) && strlen($basePath))
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
	 * @return \Zend\Uri\Http
	 */
	public function getBaseUri()
	{
		$uri = $this->getSelf();
		$uri->setPath($this->script . ($this->basePath ? $this->basePath : '/'));
		return $uri;
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
	 * @param string|array|null $query
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
			$pathInfo = ($this->basePath ? $this->basePath : '/');
		}
		elseif (strpos($pathInfo, '/') !== 0)
		{
			$pathInfo = ($this->basePath ? $this->basePath : '/') . $pathInfo;
		}
		$uri->setPath($this->script . $pathInfo)->setQuery($query)->setFragment($fragment);
		return $uri;
	}
}

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
	 * @param string $script
	 * @return \Change\Http\UrlManager
	 */
	public function __construct(\Zend\Uri\Http $self, $script = null)
	{
		$this->self = $self;
		if (is_string($script) && $script && $script[0] !== '/')
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
	public function getBaseUri()
	{
		$uri = $this->getSelf();
		$uri->setPath($this->script . $this->basePath);
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
			$pathInfo = $this->basePath;
		}
		elseif (strpos($pathInfo, '/') !== 0)
		{
			$pathInfo = ($this->basePath ? $this->basePath : '/') . $pathInfo;
		}
		$uri->setPath($this->script . $pathInfo)->setQuery($query)->setFragment($fragment);
		return $uri;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws \RuntimeException
	 * @return \Zend\Uri\Http|null
	 */
	public function getDefaultByDocument(\Change\Documents\AbstractDocument $document)
	{
		$documentPathPrefix = $document->getDocumentModelName();
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$section = $document->getPublishableFunctions()->getDefaultSection();
			if ($section === null)
			{
				return null;
			}
			return $this->getDocumentUri($document, $documentPathPrefix, $section);
		}
		throw new \RuntimeException('Document not publishable: ' . $document, 999999);
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param mixed $context
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Zend\Uri\Http|null
	 */
	public function getContextualByDocument(\Change\Documents\AbstractDocument $document, $context)
	{
		$documentPathPrefix = $document->getDocumentModelName();
		if (!$context instanceof \Change\Presentation\Interfaces\Section)
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid context', 999999);
		}
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			return $this->getDocumentUri($document, $documentPathPrefix, $context);
		}
		throw new \RuntimeException('Document not publishable: ' . $document, 999999);
	}

	/**
	 * @param \Change\Documents\Interfaces\Publishable $document
	 * @param string $documentPathPrefix
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return \Zend\Uri\Http
	 */
	protected function getDocumentUri($document, $documentPathPrefix, $section = null)
	{
		$uri = $this->getSelf();
		$path = '';
		if ($section && $section->getWebsite())
		{
			$website = $section->getWebsite();
			$uri->setHost($website->getHostName());
			$uri->setPort($website->getPort());
			if ($website->getScriptName())
			{
				$path .= $website->getScriptName() . '/';
			}
			else
			{
				$path .= '/';
			}
			if ($website->getRelativePath())
			{
				$path .= $website->getRelativePath() . '/';
			}
		}
		if (!($document instanceof \Change\Presentation\Interfaces\Website))
		{
			$path .= $this->getDocumentPath($document, $documentPathPrefix, $section);
		}
		$uri->setPath($path);
		return $uri;
	}

	/**
	 * @param \Change\Documents\Interfaces\Publishable $document
	 * @param string $documentPathPrefix
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return string
	 */
	protected function getDocumentPath($document, $documentPathPrefix, $section)
	{
		$path = $documentPathPrefix;
		if ($section)
		{
			$path .= ',' . $section->getId();
		}
		$path .= ',' . $document->getId();
		$path .= ($document instanceof \Change\Presentation\Interfaces\Section) ? '/' : '.html';
		return $path;
	}
}

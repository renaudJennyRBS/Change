<?php
namespace Change\Http;

/**
 * @name \Change\Http\Request
 */
class Request extends \Zend\Http\PhpEnvironment\Request
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var \DateTime
	 */
	protected $ifModifiedSince;

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param \DateTime|null $ifModifiedSince
	 */
	public function setIfModifiedSince($ifModifiedSince)
	{
		$this->ifModifiedSince = $ifModifiedSince;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getIfModifiedSince()
	{
		return $this->ifModifiedSince;
	}


	public function __construct()
	{
		parent::__construct();
		$this->processPath();
		$this->processIfModifiedSince();
	}

	protected function processPath()
	{
		$this->setPath($this->getServer('PATH_INFO', $this->getServer('REQUEST_URI')));
	}

	/**
	 * If-Modified-Since: Sat, 29 Oct 1994 19:43:31 GMT
	 * Accept Apache Rule :
	 * RewriteCond	%{HTTP:If-Modified-Since} !=""
	 * RewriteRule .* - [E=HTTP_IF_MODIFIED_SINCE:%{HTTP:If-Modified-Since}]
	 */
	protected function processIfModifiedSince()
	{
		if (!isset($this->serverParams['HTTP_IF_MODIFIED_SINCE']))
		{
			// This seems to be the only way to get the Authorization header on Apache
			if (function_exists('apache_request_headers'))
			{
				$apacheRequestHeaders = apache_request_headers();
				if (isset($apacheRequestHeaders['If-Modified-Since']))
				{
					$this->getHeaders()->addHeaders(array('If-Modified-Since' => $apacheRequestHeaders['If-Modified-Since']));
				}
			}
		}

		$header = $this->getHeader('If-Modified-Since');
		if ($header instanceof \Zend\Http\Header\IfModifiedSince)
		{
			$this->setIfModifiedSince($header->date());
		}
	}
}
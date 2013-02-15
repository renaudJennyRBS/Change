<?php
namespace Change\Http;

use Zend\Http\Headers;
use Zend\Http\Response;

/**
 * @name \Change\Http\Result
 */
class Result
{
	/**
	 * @var Headers|null
	 */
	protected $headers = null;

	/**
	 * @var integer
	 */
	protected $httpStatusCode;

	/**
	 * @param int $httpStatusCode
	 */
	public function setHttpStatusCode($httpStatusCode)
	{
		$this->httpStatusCode = $httpStatusCode;
	}

	/**
	 * @return int
	 */
	public function getHttpStatusCode()
	{
		return $this->httpStatusCode;
	}

	/**
	 * @param  Headers $headers
	 */
	public function setHeaders(Headers $headers)
	{
		$this->headers = $headers;
	}

	/**
	 * @return Headers
	 */
	public function getHeaders()
	{
		if ($this->headers === null) {

			$this->headers = new Headers();
		}
		return $this->headers;
	}

	/**
	 * @param string $name
	 */
	protected function removeHeader($name)
	{
		$oldHeader = $this->getHeaders()->get($name);
		if ($oldHeader)
		{
			$this->getHeaders()->removeHeader($oldHeader);
		}
	}

	/**
	 * @param string|null $location
	 */
	public function setHeaderLocation($location)
	{
		$this->removeHeader('Location');
		if ($location)
		{
			$this->getHeaders()->addHeaderLine('Location', $location);
		}
	}

	/**
	 * @param string|null $contentLocation
	 */
	public function setHeaderContentLocation($contentLocation)
	{
		$this->removeHeader('Content-Location');
		if ($contentLocation)
		{
			$this->getHeaders()->addHeaderLine('Content-Location', $contentLocation);
		}
	}

	/**
	 * @param \DateTime|null $lastModified
	 */
	public function setHeaderLastModified(\DateTime $lastModified = null)
	{
		$this->removeHeader('Last-Modified');
		if ($lastModified)
		{
			$lastModified = new \Zend\Http\Header\LastModified();
			$lastModified->setDate($lastModified);
			$this->getHeaders()->addHeader($lastModified);
		}
	}

	/**
	 * @param string $location
	 * @param integer $httpStatusCode
	 */
	public function setHeaderRedirect($location, $httpStatusCode = Response::STATUS_CODE_301)
	{
		$this->setHeaderLocation($location);
		$this->setHttpStatusCode($httpStatusCode);
	}
}
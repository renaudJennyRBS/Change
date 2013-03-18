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
	 * @param Headers $headers
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
			$header = new \Zend\Http\Header\LastModified();
			$header->setDate($lastModified);
			$this->getHeaders()->addHeader($header);
		}
	}

	/**
	 * @return \DateTime|null
	 */
	public function getHeaderLastModified()
	{
		$header = $this->getHeaders()->get('Last-Modified');
		if ($header instanceof \Zend\Http\Header\LastModified)
		{
			return $header->date();
		}
		return null;
	}

	/**
	 * @param string|null $etag
	 */
	public function setHeaderEtag($etag)
	{
		$this->removeHeader('Etag');
		if ($etag)
		{
			$this->getHeaders()->addHeaderLine('Etag', $etag);
		}
	}

	/**
	 * @return string|null
	 */
	public function getHeaderEtag()
	{
		$header = $this->getHeaders()->get('Etag');
		if ($header instanceof \Zend\Http\Header\Etag)
		{
			return $header->getFieldValue();
		}
		return null;
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return '';
	}
}
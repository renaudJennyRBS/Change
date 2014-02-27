<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @param integer $httpStatusCode
	 */
	function __construct($httpStatusCode = \Zend\Http\Response::STATUS_CODE_200)
	{
		$this->httpStatusCode = $httpStatusCode;
	}

	/**
	 * @param integer $httpStatusCode
	 */
	public function setHttpStatusCode($httpStatusCode)
	{
		$this->httpStatusCode = $httpStatusCode;
	}

	/**
	 * @return integer
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
		if ($this->headers === null)
		{

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
	 * @param string|null $contentType
	 */
	public function setHeaderContentType($contentType)
	{
		$this->removeHeader('Content-Type');
		if ($contentType)
		{
			$this->getHeaders()->addHeaderLine('Content-Type', $contentType);
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
	 * @param \DateTime|null
	 */
	public function setHeaderExpires($expires)
	{
		$this->removeHeader('Expires');
		if ($expires)
		{
			$header = new \Zend\Http\Header\Expires();
			$header->setDate($expires);
			$this->getHeaders()->addHeader($header);
		}
	}

	/**
	 * @param string|null
	 */
	public function setHeaderCacheControl($cacheControl)
	{
		$this->removeHeader('Cache-Control');
		if ($cacheControl)
		{
			$this->getHeaders()->addHeaderLine('Cache-Control', $cacheControl);
		}
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return '';
	}
}
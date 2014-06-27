<?php
namespace Change\Http;

use Zend\Http\PhpEnvironment\Response;

/**
 * @name \Change\Http\StreamResponse
 */
class StreamResponse extends Response
{

	/**
	 * @var string
	 */
	protected  $uri;

	/**
	 * @param string $uri
	 * @return $this
	 */
	public function setUri($uri)
	{
		$this->uri = $uri;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Send content
	 *
	 * @return Response
	 */
	public function sendContent()
	{
		ob_clean();
		flush();
		readfile($this->getUri());
		return $this;
	}
}
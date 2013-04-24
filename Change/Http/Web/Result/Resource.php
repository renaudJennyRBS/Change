<?php
namespace Change\Http\Web\Result;
use Change\Http\Result;

/**
 * @name \Change\Http\Web\Result\Resource
 */
class Resource extends Result
{
	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var Callable
	 */
	protected $renderer;

	/**
	 * @param string $identifier
	 */
	function __construct($identifier)
	{
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param Callable $renderer
	 */
	public function setRenderer($renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * @return Callable
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * @return boolean
	 */
	public function hasRenderer()
	{
		return ($this->renderer && is_callable($this->renderer));
	}

	/**
	 * Used for generate response
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getContent()
	{
		if ($this->hasRenderer())
		{
			return call_user_func($this->renderer);
		}
		else
		{
			throw new \RuntimeException('Renderer not set', 999999);
		}
	}
}
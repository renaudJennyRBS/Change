<?php
namespace Change\Http\Web\Result;

use Change\Http\Result;
use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Http\Web\Result\Page
 */
class Page extends Result
{
	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var array
	 */
	protected $head = array();

	/**
	 * @var Layout
	 */
	protected $templateLayout = array();

	/**
	 * @var Layout
	 */
	protected $contentLayout = array();

	/**
	 * @var Callable
	 */
	protected $renderer;

	/**
	 * @var \Change\Http\Web\Result\BlockResult[]
	 */
	protected $blockResults;

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
	 * @param array $heads
	 */
	public function addHeads(array $heads = null)
	{
		if ($heads !== null)
		{
			foreach ($heads as $key => $head)
			{
				if (is_int($key))
				{
					$this->addHeadAsString($head);
				}
				else
				{
					$this->addNamedHeadAsString($key, $head);
				}
			}
		}
	}

	/**
	 * @param string $headString
	 */
	public function addHeadAsString($headString)
	{
		$this->head[] = $headString;
	}

	/**
	 * @param string $name
	 * @param string $headString
	 */
	public function addNamedHeadAsString($name, $headString)
	{
		$this->head[$name] = $headString;
	}

	/**
	 * @param Layout $templateLayout
	 */
	public function setTemplateLayout(Layout $templateLayout)
	{
		$this->templateLayout = $templateLayout;
	}

	/**
	 * @return Layout
	 */
	public function getTemplateLayout()
	{
		return $this->templateLayout;
	}

	/**
	 * @param Layout $contentLayout
	 */
	public function setContentLayout(Layout $contentLayout)
	{
		$this->contentLayout = $contentLayout;
	}

	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return $this->contentLayout;
	}

	/**
	 * @param \Change\Http\Web\Result\BlockResult[] $blockResults
	 */
	public function setBlockResults(array $blockResults = null)
	{
		$this->blockResults = array();
		if (is_array($blockResults))
		{
			foreach($blockResults as $blockResult)
			{
				$this->addBlockResult($blockResult);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Result\BlockResult $blockResult
	 */
	public function addBlockResult(\Change\Http\Web\Result\BlockResult $blockResult)
	{
		$this->blockResults[$blockResult->getId()] = $blockResult;
	}

	/**
	 * @return \Change\Http\Web\Result\BlockResult[]
	 */
	public function getBlockResults()
	{
		return $this->blockResults === null ? array() : $this->blockResults;
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
	 * Used by template
	 * @return array
	 */
	public function getHead()
	{
		return $this->head;
	}

	/**
	 * Used by template
	 * @param $id
	 * @return string
	 */
	public function htmlBlock($id)
	{
		$br = isset($this->blockResults[$id]) ? $this->blockResults[$id] : null;
		if ($br)
		{
			$innerHTML = $br->getHtml();
			$name = $br->getName();
		}
		else
		{
			$innerHTML = null;
			$name = "unknown";
		}
		if ($innerHTML)
		{
			return '<div data-type="block" data-id="' . $id . '" data-name="' . $name . '">' . $innerHTML . '</div>';
		}
		return '<div data-type="block" class="empty" data-id="' . $id . '" data-name="' . $name . '"></div>';
	}

	/**
	 * Used for generate response
	 * @return string
	 * @throws \RuntimeException
	 */
	public function toHtml()
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
<?php
namespace Change\Http\Web\Result;

use Change\Http\Result;

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
	protected $htmlHead = array();

	/**
	 * @var string
	 */
	protected $html;

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
		$this->htmlHead[] = $headString;
	}

	/**
	 * @param string $name
	 * @param string $headString
	 */
	public function addNamedHeadAsString($name, $headString)
	{
		$this->htmlHead[$name] = $headString;
	}

	/**
	 * @param \Change\Http\Web\Result\BlockResult[] $blockResults
	 */
	public function setBlockResults(array $blockResults = null)
	{
		$this->blockResults = array();
		if (is_array($blockResults))
		{
			foreach ($blockResults as $blockResult)
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
	 * Used by template
	 * @return array
	 */
	public function getHead()
	{
		return $this->htmlHead;
	}

	/**
	 * Used by template
	 * @param string $id
	 * @param string|null $class
	 * @return string
	 */
	public function htmlBlock($id, $class = null)
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

		if ($class == 'raw')
		{
			return $innerHTML;
		}
		elseif ($innerHTML)
		{
			if ($class)
			{
				$class = ' class="' . $class . '"';
			}
			else
			{
				$class = '';
			}
			return
				'<div data-type="block" data-id="' . $id . '" data-name="' . $name . '"' . $class . '>' . $innerHTML . '</div>';
		}
		return '<div data-type="block" class="empty" data-id="' . $id . '" data-name="' . $name . '"></div>';
	}

	/**
	 * @param string $html
	 * @return $this
	 */
	public function setHtml($html)
	{
		$this->html = $html;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		return $this->html;
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		return $this->html;
	}
}
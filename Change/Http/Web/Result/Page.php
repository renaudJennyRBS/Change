<?php
namespace Change\Http\Web\Result;

use Change\Http\Result;

/**
 * @name \Change\Http\Web\Result\Page
 */
class Page extends Result
{
	/**
	 * @var integer
	 */
	protected $pageId;

	/**
	 * @var array
	 */
	protected $head = array();

	/**
	 * @var \Change\Http\Web\Layout\Item[]
	 */
	protected $contentLayout = array();

	/**
	 * @var \Change\Http\Web\Blocks\Result[]
	 */
	protected $blockResults;

	/**
	 * @var integer
	 */
	protected $templateId;

	/**
	 * @var string
	 */
	protected $htmlTemplate;

	/**
	 * @var \Change\Http\Web\Layout\Item[]
	 */
	protected $templateLayout = array();

	/**
	 * @var array
	 */
	protected $htmlFragments = array();


	/**
	 * @var Callable
	 */
	protected $renderer;

	/**
	 * @param integer $pageId
	 */
	function __construct($pageId)
	{
		$this->pageId = $pageId;
	}

	/**
	 * @return integer
	 */
	public function getPageId()
	{
		return $this->pageId;
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
	 * @param integer $templateId
	 */
	public function setTemplateId($templateId)
	{
		$this->templateId = $templateId;
	}

	/**
	 * @return integer
	 */
	public function getTemplateId()
	{
		return $this->templateId;
	}


	/**
	 * @param string $htmlTemplate
	 */
	public function setHtmlTemplate($htmlTemplate)
	{
		$this->htmlTemplate = $htmlTemplate;
	}

	/**
	 * @return string
	 */
	public function getHtmlTemplate()
	{
		return $this->htmlTemplate;
	}

	/**
	 * @param \Change\Http\Web\Layout\Item[] $templateLayout
	 */
	public function setTemplateLayout($templateLayout)
	{
		$this->templateLayout = (is_array($templateLayout)) ? $templateLayout : array();
	}

	/**
	 * @return \Change\Http\Web\Layout\Item[]
	 */
	public function getTemplateLayout()
	{
		return $this->templateLayout;
	}

	/**
	 * @param \Change\Http\Web\Layout\Item[] $contentLayout
	 */
	public function setContentLayout($contentLayout)
	{
		$this->contentLayout = (is_array($contentLayout)) ? $contentLayout : array();
	}

	/**
	 * @return \Change\Http\Web\Layout\Item[]
	 */
	public function getContentLayout()
	{
		return $this->contentLayout;
	}

	/**
	 * @param \Change\Http\Web\Blocks\Result[] $blockResults
	 */
	public function setBlockResults($blockResults)
	{
		$this->blockResults = (is_array($blockResults)) ? $blockResults : array();
	}

	/**
	 * @return \Change\Http\Web\Blocks\Result[]
	 */
	public function getBlockResults()
	{
		return $this->blockResults;
	}

	/**
	 * @return string
	 */
	public function getHeadAsString()
	{
		return implode(PHP_EOL . "\t", $this->head);
	}

	public function addHtmlFragment($name, $html)
	{
		$this->htmlFragments[$name] = $html;
	}

	/**
	 * @return array
	 */
	public function getHtmlFragments()
	{
		return $this->htmlFragments;
	}

	/**
	 * @param array $head
	 */
	public function setHead($head)
	{
		$this->head = $head;
	}

	/**
	 * @return array
	 */
	public function getHead()
	{
		return $this->head;
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
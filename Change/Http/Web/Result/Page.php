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

	protected $head = array();

	public function addHeadAsString($headItem)
	{
		$this->head[] = $headItem;
	}

	public function addNamedHeadAsString($name, $headItem)
	{
		$this->head[$name] = $headItem;
	}

	/**
	 * @param integer $pageId
	 */
	function __construct($pageId)
	{
		$this->pageId = $pageId;
	}

	public function toHtml()
	{
		return '<!DOCTYPE html>
<html>
<head>
	' . implode(PHP_EOL . "\t", $this->head) .'
</head>
<body>
<h1>Page Id: '.$this->pageId.'</h1><a href="test,1023.html">Page Id:1023</a>
</body>
</html>';
	}
}
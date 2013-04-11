<?php
namespace Change\Http\Web\Blocks;

/**
 * @name \Change\Http\Web\Blocks\Result
 */
class Result
{
	/**
	 * @var string
	 */
	protected $etag;

	/**
	 * @var array
	 */
	protected $head = array();

	/**
	 * @var string
	 */
	protected $html;

	/**
	 * @param string $etag
	 */
	public function setEtag($etag)
	{
		$this->etag = $etag;
	}

	/**
	 * @return string
	 */
	public function getEtag()
	{
		return $this->etag;
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
	 * @param string $headString
	 * @return $this
	 */
	public function addHeadAsString($headString)
	{
		$this->head[] = $headString;
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $headString
	 * @return $this
	 */
	public function addNamedHeadAsString($name, $headString)
	{
		$this->head[$name] = $headString;
		return $this;
	}

	/**
	 * @param string $html
	 */
	public function setHtml($html)
	{
		$this->html = $html;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		return $this->html;
	}
}
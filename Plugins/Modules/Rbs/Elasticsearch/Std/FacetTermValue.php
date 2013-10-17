<?php
namespace Rbs\Elasticsearch\Std;

/**
* @name \Rbs\Elasticsearch\Std\FacetTermValue
*/
class FacetTermValue
{
	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $valueTitle;

	/**
	 * @var integer|null
	 */
	protected $count;

	/**
	 * @var boolean
	 */
	protected $filtered;

	/**
	 * @param string $value
	 * @param string|null $valueTitle
	 */
	function __construct($value, $valueTitle = null)
	{
		$this->value = $value;
		$this->valueTitle = $valueTitle !== null ? $valueTitle : $value;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $valueTitle
	 * @return $this
	 */
	public function setValueTitle($valueTitle)
	{
		$this->valueTitle = $valueTitle;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValueTitle()
	{
		return $this->valueTitle;
	}

	/**
	 * @param int|null $count
	 * @return $this
	 */
	public function setCount($count)
	{
		$this->count = $count;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getCount()
	{
		return $this->count;
	}

	/**
	 * @param boolean $filtered
	 * @return $this
	 */
	public function setFiltered($filtered)
	{
		$this->filtered = $filtered;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getFiltered()
	{
		return $this->filtered;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->getValueTitle();
		return $this->count !== null ? $title . ' ('.$this->count.')' : $title;
	}
}
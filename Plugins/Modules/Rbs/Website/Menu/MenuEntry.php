<?php
namespace Rbs\Website\Menu;

/**
 * @name \Rbs\Website\Menu\MenuEntry
 */
class MenuEntry
{
	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var boolean
	 */
	protected $inPath = false;

	/**
	 * @var boolean
	 */
	protected $current = false;

	/**
	 * @var MenuEntry[]
	 */
	protected $children;

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setTitle($label)
	{
		$this->title = $label;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return bool
	 */
	public function hasUrl()
	{
		return $this->url !== null;
	}

	/**
	 * @param boolean $inPath
	 * @return $this
	 */
	public function setInPath($inPath)
	{
		$this->inPath = $inPath;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isInPath()
	{
		return $this->inPath;
	}

	/**
	 * @param boolean $current
	 * @return $this
	 */
	public function setCurrent($current)
	{
		$this->current = $current;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isCurrent()
	{
		return $this->current;
	}

	/**
	 * @param MenuEntry[] $children
	 * @return $this
	 */
	public function setChildren($children)
	{
		$this->children = $children;
		return $this;
	}

	/**
	 * @param MenuEntry $child
	 * @return $this
	 */
	public function addChild($child)
	{
		if ($child instanceof MenuEntry)
		{
			$this->children[] = $child;
		}
		return $this;
	}

	/**
	 * @return MenuEntry[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @return bool
	 */
	public function hasChild()
	{
		return is_array($this->children) && count($this->children) > 0;
	}
}
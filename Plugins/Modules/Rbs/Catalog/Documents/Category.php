<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\Category
 */
class Category extends \Compilation\Rbs\Catalog\Documents\Category
{
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getSection() ? $this->getSection()->getTitle() : null;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		// Do nothing.
		return $this;
	}

	public function getPublicationSections()
	{
		if ($this->getSection())
		{
			return array($this->getSection());
		}
		return array();
	}
}
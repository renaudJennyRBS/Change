<?php
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Rbs\Theme\Documents\PageTemplate
 */
class PageTemplate extends \Compilation\Rbs\Theme\Documents\PageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getDecodedEditableContent());
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getId();
	}
}
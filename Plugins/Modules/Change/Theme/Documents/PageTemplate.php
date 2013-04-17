<?php
namespace Change\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Theme\Documents\PageTemplate
 */
class PageTemplate extends \Compilation\Change\Theme\Documents\PageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return null;
	}

	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getDecodedEditableContent());
	}
}
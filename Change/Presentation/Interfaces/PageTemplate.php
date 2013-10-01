<?php
namespace Change\Presentation\Interfaces;

use Change\Presentation\Layout\Layout;

/**
 * @package Change\Presentation\Interfaces
 * @name \Change\Presentation\Interfaces\PageTemplate
 */
interface PageTemplate
{
	/**
	 * @return \Change\Presentation\Interfaces\Theme
	 */
	public function getTheme();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getHtml();

	/**
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout();

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();
}
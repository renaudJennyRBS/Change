<?php
namespace Change\Presentation\Interfaces;

/**
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
	 * @param integer $websiteId
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout($websiteId = null);

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();
}
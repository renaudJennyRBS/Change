<?php
namespace Change\Presentation\Interfaces;

use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Presentation\Interfaces\Page
 */
interface Page
{
	/**
	 * @api
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();

	/**
	 * @api
	 * @return Template
	 */
	public function getTemplate();

	/**
	 * @return Layout
	 */
	public function getContentLayout();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection();

	/**
	 * @return integer
	 */
	public function getTTL();

}
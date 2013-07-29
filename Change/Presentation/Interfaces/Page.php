<?php
namespace Change\Presentation\Interfaces;

use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Presentation\Interfaces\Page
 */
interface Page
{
	const EVENT_PAGE_PREPARE = 'page.prepare';

	const EVENT_PAGE_COMPOSE = 'page.compose';

	/**
	 * Retrieve the event manager
	 * @api
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	public function getEventManager();

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
	 * @return PageTemplate
	 */
	public function getPageTemplate();

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

}
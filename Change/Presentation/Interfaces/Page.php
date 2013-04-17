<?php
namespace Change\Presentation\Interfaces;

use Change\Presentation\Layout\Layout;
/**
 * @package Change\Presentation\Interfaces
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

}
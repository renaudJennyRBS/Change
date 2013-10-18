<?php
namespace Change\Presentation\Pages;

use Change\Presentation\Interfaces\Page;
use Zend\EventManager\Event;

/**
 * @name \Change\Presentation\Pages\PageEvent
 */
class PageEvent extends Event
{
	/**
	 * @return \Change\Presentation\Interfaces\Page|null
	 */
	public function getPage()
	{
		$page = $this->getParam('page');
		return $page instanceof Page ? $page : null;
	}

	/**
	 * @return \Change\Http\Web\Result\Page|null
	 */
	public function getPageResult()
	{
		$pageResult = $this->getParam('pageResult');
		return $pageResult instanceof \Change\Http\Web\Result\Page ? $pageResult : null;
	}

	/**
	 * @return \Change\Presentation\Pages\PageManager|null
	 */
	public function getPageManager()
	{
		if ($this->getTarget() instanceof PageManager)
		{
			return $this->getTarget();
		}
		return null;
	}
}
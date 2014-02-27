<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Pages;

use Change\Presentation\Interfaces\Page;

/**
 * @name \Change\Presentation\Pages\PageEvent
 */
class PageEvent extends \Change\Events\Event
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
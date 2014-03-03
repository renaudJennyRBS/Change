<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Mail\Documents;

/**
 * @name \Rbs\Mail\Documents\Mail
 */
class Mail extends \Compilation\Rbs\Mail\Documents\Mail implements \Change\Presentation\Interfaces\Page
{
	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId() . ',' . $this->getCurrentLCID();
	}

	/**
	 * @return \Datetime|null
	 */
	public function getModificationDate()
	{
		return $this->getCurrentLocalization()->getModificationDate();
	}

	/**
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout()
	{
		return new \Change\Presentation\Layout\Layout($this->getCurrentLocalization()->getEditableContent());
	}

	/**
	 * @return string|null
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getSubject();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		//FIXME how to find the correct website in websites?
		return count($this->getWebsites()) ? $this->getWebsites()[0] : null;
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		if (!$this->getIsVariation())
		{
			throw new \RuntimeException('can not delete an original mail', 999999);
		}
	}
}

<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Documents;

/**
 * @name \Rbs\Geo\Documents\Zone
 */
class Zone extends \Compilation\Rbs\Geo\Documents\Zone
{
	/**
	 * @return null|string
	 */
	public function getTitle()
	{
		$title = $this->getCurrentLocalization()->getTitle();
		if (\Change\Stdlib\String::isEmpty($title) && $this->getRefLCID() != $this->getCurrentLCID()) {
			$title = $this->getRefLocalization()->getTitle();
		}
		return \Change\Stdlib\String::isEmpty($title) ? $this->getCode() : $title;
	}
}

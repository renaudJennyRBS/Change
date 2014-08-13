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
 * @name \Rbs\Geo\Documents\AddressField
 */
class AddressField extends \Compilation\Rbs\Geo\Documents\AddressField implements \Change\Collection\ItemInterface
{
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->getCode();
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->getCurrentLocalization()->isNew() ? $this->getRefLocalization()
			->getTitle() : $this->getCurrentLocalization()->getTitle();
		return $title === null ? $this->getCode() : $title;
	}
}

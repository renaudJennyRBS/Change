<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\geo\Documents;

/**
 * @name \Rbs\geo\Documents\Country
 */
class Country extends \Compilation\Rbs\Geo\Documents\Country
{
	/**
	 * @return string
	 */
	public function getI18nTitleKey()
	{
		return 'm.rbs.geo.countries.' . strtolower($this->getCode());
	}
}

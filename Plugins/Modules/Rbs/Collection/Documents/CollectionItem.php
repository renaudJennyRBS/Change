<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Collection\Documents;

/**
* @name \Rbs\Collection\Documents\CollectionItem
*/
class CollectionItem extends \Compilation\Rbs\Collection\Documents\CollectionItem implements \Change\Collection\ItemInterface
{
	/**
	 * @return string|null
	 */
	public function getTitle()
	{
		$localization = $this->getCurrentLocalization();
		if ($localization->isNew())
		{
			$localization = $this->getRefLocalization();
		}
		$title =  $localization->getTitle();
		return $title === null ? $this->getLabel() : $title;
	}

	public function processRestValue($restValue, $urlManager)
	{
		unset($restValue['locked']);
		parent::processRestValue($restValue, $urlManager);
	}
}
<?php
/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Highlight\Documents;

/**
 * @name \Rbs\Highlight\Documents\Highlight
 */
class Highlight extends \Compilation\Rbs\Highlight\Documents\Highlight
{
	/**
	 * @return \Rbs\Highlight\Documents\HighlightItem[]
	 */
	public function getActiveItems()
	{
		$items = [];
		foreach ($this->getItems() as $item)
		{
			if ($item->activated())
			{
				$items[] = $item;
			}
		}
		return $items;
	}
}
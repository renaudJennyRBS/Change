<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Collection;

use Change\Collection\CollectionArray;
use Change\Events\Event;

/**
 * @name \Rbs\Media\Collection\Collections
 */
class Collections
{
	/**
	 * @param Event $event
	 */
	public function addBlockAlignments(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$collection = new CollectionArray('Rbs_Media_BlockAlignments', array(
				'left' => $i18nManager->trans('m.rbs.media.admin.block_alignment_left'),
				'right' => $i18nManager->trans('m.rbs.media.admin.block_alignment_right'),
				'center' => $i18nManager->trans('m.rbs.media.admin.block_alignment_center'),
				'fill' => $i18nManager->trans('m.rbs.media.admin.block_alignment_fill')
			));
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
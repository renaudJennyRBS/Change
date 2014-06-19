<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Commerce\Admin\GetModelTwigAttributes
 */
class GetModelTwigAttributes
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$view = $event->getParam('view');
		$model = $event->getParam('model');

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager && $model instanceof \Change\Documents\AbstractModel)
		{
			if ($view === 'edit' && $model->getName() == 'Rbs_Commerce_Process')
			{
				$attributes = $event->getParam('attributes');

				$attributes['asideDirectives'][] = ['name' => 'rbs-aside-commerce-process-fees-and-discounts'];

				$event->setParam('attributes', $attributes);
			}
		}
	}
}
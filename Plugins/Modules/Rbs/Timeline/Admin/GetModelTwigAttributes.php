<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Timeline\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Timeline\Admin\GetModelTwigAttributes
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
			if ($model->isEditable() && ($view === 'edit' || $view === 'translate'))
			{
				$attributes = $event->getParam('attributes');

				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$attributes['links'][] = [
					'name' => 'timeline',
					'href' => '(= document | rbsURL:\'timeline\' =)',
					'description' => $i18nManager->trans('m.rbs.timeline.admin.admin_view_timeline', ['ucf'])
				];

				$event->setParam('attributes', $attributes);
			}
		}
	}
}
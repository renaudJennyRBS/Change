<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Price\Admin\GetModelTwigAttributes
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

		/* @var $model \Change\Documents\AbstractModel */
		$model = $event->getParam('model');

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager && $model instanceof \Change\Documents\AbstractModel)
		{
			$attributes = $event->getParam('attributes');

			//$attributes shouldn't be empty
			if (!is_array($attributes))
			{
				$attributes = [];
			}

			//$attributes['asideDirectives'] can be empty
			if (!isset($attributes['asideDirectives']))
			{
				$attributes['asideDirectives'] = [];
			}

			$modelName = $model->getName();
			if ($view === 'edit' && $modelName === 'Rbs_Price_Price')
			{
				$attributes['asideDirectives'][] = ['name' => 'rbs-aside-price'];
			}
			$event->setParam('attributes', $attributes);
		}
	}
}
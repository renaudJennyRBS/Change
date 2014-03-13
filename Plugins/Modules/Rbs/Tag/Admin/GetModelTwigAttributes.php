<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Tag\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Tag\Admin\GetModelTwigAttributes
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

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager)
		{
			if ($view == 'edit' || $view == 'translate')
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

				$asideDirectives = [
					[
						'name' => 'rbs-aside-tag-selector',
						'attributes' => [
							['name' => 'document', 'value' => 'document']
						]
					]
				];

				$attributes['asideDirectives'] = array_merge($attributes['asideDirectives'], $asideDirectives);

				$event->setParam('attributes', $attributes);
			}
		}
	}
}
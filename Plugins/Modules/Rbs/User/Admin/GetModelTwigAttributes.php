<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\User\Admin\GetModelTwigAttributes
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
			if ($model->getRootName() == 'Rbs_User_Group' && $view == 'edit')
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$links = [
					[
						'name' => 'groupUsers',
						'href' => '(= document | rbsURL:\'groupUsers\' =)',
						'description' => $i18nManager->trans('m.rbs.user.admin.users_in_group_aside_link', ['ucf'])
					],
					[
						'name' => 'permissions',
						'href' => '(= document | rbsURL:\'permissions\' =)',
						'description' => $i18nManager->trans('m.rbs.user.admin.permissions_aside_link', ['ucf'])
					]
				];
				$this->addLinks($event, $links);
			}
			elseif ($model->getRootName() == 'Rbs_User_User' && $view == 'edit')
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$links = [
					[
						'name' => 'applications',
						'href' => '(= document | rbsURL:\'applications\' =)',
						'description' => $i18nManager->trans('m.rbs.user.admin.applications_aside_link', ['ucf'])
					],
					[
						'name' => 'permissions',
						'href' => '(= document | rbsURL:\'permissions\' =)',
						'description' => $i18nManager->trans('m.rbs.user.admin.permissions_aside_link', ['ucf'])
					]
				];
				$this->addLinks($event, $links);
			}
		}
	}

	/**
	 * @param Event $event
	 * @param array[] $links
	 */
	protected function addLinks(Event $event, $links)
	{
		$attributes = $event->getParam('attributes');
		if (!is_array($attributes))
		{
			$attributes = [];
		}

		$attributes['links'] = array_merge(isset($attributes['links']) ? $attributes['links'] : [], $links);

		$event->setParam('attributes', $attributes);
	}
}
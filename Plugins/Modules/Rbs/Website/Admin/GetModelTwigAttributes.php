<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Website\Admin\GetModelTwigAttributes
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

		$attributes = $event->getParam('attributes');
		//$attributes shouldn't be empty
		if (!is_array($attributes))
		{
			$attributes = [];
		}

		//$attributes['links'] can be empty
		if (!isset($attributes['links']))
		{
			$attributes['links'] = [];
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager)
		{
			$modelName = $model->getName();
			if (($view === 'edit' || $view === 'new' || $view === 'translate') &&
				($modelName === 'Rbs_Website_StaticPage' || $modelName === 'Rbs_Website_FunctionalPage'))
			{
				$links = [
					[
						'name' => 'pageTemplate',
						'href' => '(= document.pageTemplate | rbsURL =)',
						'description' => $i18nManager->trans('m.rbs.website.admin.page_edit_template', ['ucf']),
						'attributes' => [
							['name' => 'data-ng-show', 'value' => 'document.pageTemplate']
						]
					]
				];

				$attributes['links'] = array_merge($attributes['links'], $links);
			}
			else if ($view === 'edit' && $modelName === 'Rbs_Website_Website')
			{
				$links = [
					[
						'name' => 'structure',
						'href' => '(= document | rbsURL:\'structure\' =)',
						'description' => $i18nManager->trans('m.rbs.website.admin.manage_website_structure', ['ucf'])
					],
					[
						'name' => 'menus',
						'href' => '(= document | rbsURL:\'menus\' =)',
						'description' => $i18nManager->trans('m.rbs.website.admin.manage_website_menus', ['ucf'])
					]
				];

				$attributes['links'] = array_merge($attributes['links'], $links);
			}
		}
		$event->setParam('attributes', $attributes);
	}
}
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
		$model = $event->getParam('model');

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager && $model instanceof \Change\Documents\AbstractModel)
		{
			$attributes = $event->getParam('attributes');
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$modelName = $model->getName();

			if (($view === 'edit' || $view === 'new' || $view === 'translate') &&
				($modelName === 'Rbs_Website_StaticPage' || $modelName === 'Rbs_Website_FunctionalPage'))
			{
				$attributes['links'][] = [
					'name' => 'pageTemplate',
					'href' => '(= document.pageTemplate | rbsURL =)',
					'description' => $i18nManager->trans('m.rbs.website.admin.page_edit_template', ['ucf']),
					'attributes' => [
						['name' => 'data-ng-show', 'value' => 'document.pageTemplate']
					]
				];
			}
			else if ($view === 'edit' && $modelName === 'Rbs_Website_Website')
			{
				$attributes['links'][] = [
					'name' => 'structure',
					'href' => '(= document | rbsURL:\'structure\' =)',
					'description' => $i18nManager->trans('m.rbs.website.admin.manage_website_structure', ['ucf'])
				];
				$attributes['links'][] = [
					'name' => 'menus',
					'href' => '(= document | rbsURL:\'menus\' =)',
					'description' => $i18nManager->trans('m.rbs.website.admin.manage_website_menus', ['ucf'])
				];
			}

			$event->setParam('attributes', $attributes);
		}
	}
}
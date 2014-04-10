<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Stock\Admin\GetModelTwigAttributes
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
		$modelName = $model->getName();

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager && ($modelName === 'Rbs_Stock_InventoryEntry' || $modelName === 'Rbs_Stock_Sku'))
		{
			if ($model->isEditable() && $view === 'edit')
			{
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

				if ($modelName === 'Rbs_Stock_InventoryEntry')
				{
					$links = [
						[
							'name' => 'movement',
							'href' => '(= document.sku | rbsURL:\'movement\' =)',
							'description' => $i18nManager->trans('m.rbs.stock.admin.see_movement', ['ucf'])
						],
						[
							'name' => 'reservation',
							'href' => '(= document.sku | rbsURL:\'reservation\' =)',
							'description' => $i18nManager->trans('m.rbs.stock.admin.see_reservation', ['ucf'])
						]
					];
				}
				else
				{
					$links = [
						[
							'name' => 'movement',
							'href' => '(= document | rbsURL:\'movement\' =)',
							'description' => $i18nManager->trans('m.rbs.stock.admin.see_movement', ['ucf'])
						],
						[
							'name' => 'reservation',
							'href' => '(= document | rbsURL:\'reservation\' =)',
							'description' => $i18nManager->trans('m.rbs.stock.admin.see_reservation', ['ucf'])
						]
					];
				}

				$attributes['links'] = array_merge($attributes['links'], $links);

				$event->setParam('attributes', $attributes);
			}
		}
	}
}
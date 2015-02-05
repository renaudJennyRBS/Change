<?php
namespace Rbs\Social\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Social\Admin\GetModelTwigAttributes
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

		if ($view == 'edit' && $model->isPublishable())
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

			$links = [
				[
					'name' => 'social',
					'href' => '(= document | rbsURL:\'social\' =)',
					'description' => $i18nManager->trans('m.rbs.social.admin.admin_view_social', ['ucf'])
				]
			];

			$attributes['links'] = array_merge($attributes['links'], $links);

			$event->setParam('attributes', $attributes);
		}
	}
}
<?php
namespace Rbs\Event\Presentation;

/**
* @name \Rbs\Event\Presentation\PageFunctions
*/
class PageFunctions
{
	public function addFunctions(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$functions[] = ['code' => 'Rbs_Event_Event', 'document' => true, 'block' => 'Rbs_Event_Event',
			'label' => $i18nManager->trans('m.rbs.event.admin.event_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.event.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Event_News', 'document' => true, 'block' => 'Rbs_Event_News',
			'label' => $i18nManager->trans('m.rbs.event.admin.news_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.event.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Event_Category', 'document' => true, 'block' => 'Rbs_Event_Category',
			'label' => $i18nManager->trans('m.rbs.event.admin.category_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.event.admin.module_name', $ucf)];

		$event->setParam('functions', $functions);
	}
} 
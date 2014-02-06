<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\EventInformation
 */
class EventInformation extends \Rbs\Event\Blocks\Base\BaseEventInformation
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.event.admin.event_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Event_Event', $i18nManager);
		$this->getParameterInformation('templateName')->setDefaultValue('event.twig');
	}
}

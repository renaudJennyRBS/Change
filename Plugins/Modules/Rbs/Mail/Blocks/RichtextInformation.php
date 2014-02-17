<?php
namespace Rbs\Mail\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Mail\Blocks\RichtextInformation
 */
class RichtextInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.mail.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.mail.admin.richtext', $ucf));
		$this->addInformationMeta('contentType', Property::TYPE_STRING, true, 'Markdown');
		$this->setMailSuitable(true);
	}
}
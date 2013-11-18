<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Website\Blocks\RichtextInformation
 */
class RichtextInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.richtext', $ucf));
		$this->addInformationMeta('contentType', Property::TYPE_STRING, true, 'Markdown');
	}
}
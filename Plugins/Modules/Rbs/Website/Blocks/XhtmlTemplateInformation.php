<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Website\Blocks\XhtmlTemplateInformation
 */
class XhtmlTemplateInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans($i18nManager->trans('m.rbs.website.admin.xhtml_template', $ucf)));
		$this->addInformationMeta('moduleName', Property::TYPE_STRING, true, 'Rbs_Website');
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false);
	}
}
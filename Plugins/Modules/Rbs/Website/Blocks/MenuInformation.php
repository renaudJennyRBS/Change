<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\MenuInformation
 */
class MenuInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.menu', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, true, 'menu-vertical.twig')
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu-templatename', $ucf));
		$this->addInformationMeta('documentId', Property::TYPE_DOCUMENTID)
			->setAllowedModelsNames(array('Rbs_Website_Section', 'Rbs_Website_Menu'))
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu-documentid', $ucf));
		$this->addInformationMeta('maxLevel', Property::TYPE_INTEGER, true, 1)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu-maxlevel', $ucf));
		$this->addInformationMeta('showTitle', Property::TYPE_BOOLEAN, true, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu-showtitle', $ucf));
	}
}

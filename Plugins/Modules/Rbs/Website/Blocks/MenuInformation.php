<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\MenuInformation
 */
class MenuInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.menu', $ucf));
		$this->addInformationMeta('contextual', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu_contextual', $ucf));
		$this->addInformationMetaForDetailBlock(array('Rbs_Website_Topic', 'Rbs_Website_Website', 'Rbs_Website_Menu'), $i18nManager)
			->setNormalizeCallback(function ($parametersValues) {
				$contextual = isset($parametersValues['contextual']) ? $parametersValues['contextual'] : false;
				if ($contextual)
				{
					return null;
				}
				$propertyName = \Change\Presentation\Blocks\Standard\Block::DOCUMENT_TO_DISPLAY_PROPERTY_NAME;
				return isset($parametersValues[$propertyName]) ? intval($parametersValues[$propertyName]) : 0;
			});
		$this->addInformationMeta('offset', Property::TYPE_INTEGER, true, 0)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu_offset', $ucf))
			->setNormalizeCallback(function ($parametersValues) {
				$contextual = isset($parametersValues['contextual']) ? $parametersValues['contextual'] : false;
				if (!$contextual)
				{
					return null;
				}
				return isset($parametersValues['offset']) ? intval($parametersValues['offset']) : 0;
			});
		$this->addInformationMeta('maxLevel', Property::TYPE_INTEGER, true, 1)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu_maxlevel', $ucf));
		$this->addInformationMeta('showTitle', Property::TYPE_BOOLEAN, true, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.menu_showtitle', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Website', 'menu-contextual.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.website.admin.template_menu_contextual_label', ['ucf']));
		$templateInformation->addParameterInformation('deployAll', Property::TYPE_BOOLEAN, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_menu_deploy_all', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Website', 'menu-vertical.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.website.admin.template_menu_vertical_label', ['ucf']));
		$templateInformation->addParameterInformation('deployAll', Property::TYPE_BOOLEAN, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_menu_deploy_all', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Website', 'menu-scroll.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.website.admin.template_menu_scroll_label', ['ucf']));
		$templateInformation->addParameterInformation('inverseColors', Property::TYPE_BOOLEAN, true)
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_menu_inverse_colors', $ucf));
	}
}

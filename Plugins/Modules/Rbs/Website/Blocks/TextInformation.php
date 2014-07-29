<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\TextInformation
 */
class TextInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.generic.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.text', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Website_Text', $i18nManager);
		$this->addTTL(600);

		$templateInformation = $this->addTemplateInformation('Rbs_Website', 'text.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.website.admin.text_only', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Website', 'textWithTitle.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.website.admin.text_with_title', $ucf));
		$templateInformation->addParameterInformation('titleLevel', \Change\Documents\Property::TYPE_INTEGER, false, 1)
			->setLabel($i18nManager->trans('m.rbs.website.admin.text_title_level', $ucf))
			->setNormalizeCallback(function ($parametersValues) {
				$value = isset($parametersValues['titleLevel']) ? intval($parametersValues['titleLevel']) : 1;
				return ($value >= 1 && $value <= 6) ? $value : null;
			});
	}
}
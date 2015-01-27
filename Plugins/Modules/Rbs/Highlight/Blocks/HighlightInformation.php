<?php
/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Highlight\Blocks;

/**
 * @name \Rbs\Highlight\Blocks\HighlightInformation
 */
class HighlightInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.highlight.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.highlight.admin.highlight_label', $ucf));
		$this->addParameterInformationForDetailBlock('Rbs_Highlight_Highlight', $i18nManager);

		$defaultInformation = $this->addDefaultTemplateInformation();
		$defaultInformation->setLabel($i18nManager->trans('m.rbs.media.admin.template_slider_label', ['ucf']));
		$defaultInformation->addParameterInformation('height', \Change\Documents\Property::TYPE_STRING, false, 240)
			->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_carousel_height', ['ucf']))
			->setNormalizeCallback(function ($parametersValues) {
				$value = isset($parametersValues['height']) ? intval($parametersValues['height']) : 240;
				return ($value <= 50) ? 50 : $value;});
		$defaultInformation->addParameterInformation('interval', \Change\Documents\Property::TYPE_INTEGER, false, 5000)
			->setLabel($i18nManager->trans('m.rbs.media.admin.template_slider_interval', ['ucf']))
			->setNormalizeCallback(function ($parametersValues) {
				$value = isset($parametersValues['interval']) ? intval($parametersValues['interval']) : 5000;
				return ($value <= 500) ? 500 : $value;});
		$defaultInformation->addParameterInformation('showTitle', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_show_title', $ucf));
		$defaultInformation->addParameterInformation('showDescription', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_show_description', $ucf));
		$defaultInformation->addParameterInformation('showLinkToDetail', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_show_link_to_detail', $ucf));
	}
}
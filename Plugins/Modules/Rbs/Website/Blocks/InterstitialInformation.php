<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\InterstitialInformation
 */
class InterstitialInformation extends \Change\Presentation\Blocks\Information
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
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_label', $ucf));
		$this->addInformationMeta('popinTitle', \Change\Documents\Property::TYPE_STRING)
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_popin_title', $ucf));
		$this->addInformationMeta('displayedPage', \Change\Documents\Property::TYPE_DOCUMENT)
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_displayed_page', $ucf))
			->setAllowedModelsNames('Rbs_Website_StaticPage');
		$this->addInformationMeta('popinSize', \Change\Documents\Property::TYPE_STRING, false, 'medium')
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_popin_size', $ucf))
			->setCollectionCode('Rbs_Website_InterstitialPopinSizes');
		$this->addInformationMeta('displayFrequency', \Change\Documents\Property::TYPE_STRING, true, 'reprieve')
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_display_frequency', $ucf))
			->setCollectionCode('Rbs_Website_InterstitialDisplayFrequencies');
		$this->addInformationMeta('displayReprieve', \Change\Documents\Property::TYPE_INTEGER, false, 30)
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_display_reprieve', $ucf))
			->setNormalizeCallback(function ($parametersValues) {
				$displayFrequency = isset($parametersValues['displayFrequency']) ? $parametersValues['displayFrequency'] : 'reprieve';
				if ($displayFrequency != 'reprieve')
				{
					return null;
				}
				return isset($parametersValues['displayReprieve']) ? intval($parametersValues['displayReprieve']) : 30;
			});
		$this->addInformationMeta('audience', \Change\Documents\Property::TYPE_STRING, true, 'all')
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_audience', $ucf))
			->setCollectionCode('Rbs_Website_InterstitialAudiences');
		$this->addInformationMeta('allowClosing', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_allow_closing', $ucf));
		$this->addInformationMeta('autoCloseDelay', \Change\Documents\Property::TYPE_INTEGER)
			->setLabel($i18nManager->trans('m.rbs.website.admin.interstitial_auto_close_delay', $ucf))
			->setNormalizeCallback(function ($parametersValues) {
				$allowClosing = isset($parametersValues['allowClosing']) ? $parametersValues['allowClosing'] : true;
				if (!$allowClosing)
				{
					return null;
				}
				return isset($parametersValues['autoCloseDelay']) ? intval($parametersValues['autoCloseDelay']) : null;
			});
	}
}
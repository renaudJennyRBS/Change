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
 * @name \Rbs\Website\Blocks\CookieAskConsentInformation
 */
class TrackersAskConsentInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_ask_consent_label', $ucf));

		$this->addParameterInformation('askConsentText', \Change\Documents\Property::TYPE_DOCUMENT, true)
			->setAllowedModelsNames('Rbs_Website_Text')
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_ask_consent_text', $ucf));
		$this->addParameterInformation('optOutConfirmationText', \Change\Documents\Property::TYPE_DOCUMENT, true)
			->setAllowedModelsNames('Rbs_Website_Text')
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_opt_out_confirmation_text', $ucf));
		$this->addParameterInformation('optInConfirmationText', \Change\Documents\Property::TYPE_DOCUMENT, true)
			->setAllowedModelsNames('Rbs_Website_Text')
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_opt_in_confirmation_text', $ucf));
	}
}
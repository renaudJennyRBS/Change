<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Blocks;

/**
 * @name \Rbs\Order\Blocks\CreditNoteSummaryInformation
 */
class CreditNoteSummaryInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.order.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.order.admin.credit_note_summary_label', $ucf));

		$this->addParameterInformation('showIfEmpty', \Change\Documents\Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.order.admin.credit_note_summary_show_if_empty', $ucf));
		$this->addParameterInformation('usage', \Change\Documents\Property::TYPE_DOCUMENT, true)
			->setAllowedModelsNames('Rbs_Website_Text')
			->setLabel($i18nManager->trans('m.rbs.website.admin.credit_note_usage', $ucf));
	}
}
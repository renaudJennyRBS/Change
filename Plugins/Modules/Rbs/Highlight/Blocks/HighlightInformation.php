<?php
/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Highlight\Blocks;

use Change\Documents\Property;

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
		$this->addInformationMetaForDetailBlock('Rbs_Highlight_Highlight', $i18nManager);
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Blocks;

/**
 * @name \Rbs\Media\Blocks\VideoInformation
 */
class VideoInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.media.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.media.admin.video_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Media_Video', $i18nManager);

		$this->addInformationMeta('alignment', \Change\Documents\Property::TYPE_STRING, false, 'left')
			->setCollectionCode('Rbs_Media_BlockAlignments')
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_video_alignment', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Media', 'video-thumbnail.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.media.admin.template_thumbnail_label', ['ucf']));
		$templateInformation->addParameterInformation('thumbnailTitle', \Change\Documents\Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_thumbnail_title', $ucf));
		$templateInformation->addParameterInformation('thumbnailText', \Change\Documents\Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_thumbnail_text', $ucf));
	}
}
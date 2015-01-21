<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Blocks;

/**
 * @name \Rbs\Media\Blocks\FileInformation
 */
class FileInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.media.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.media.admin.file_label', $ucf));
		$this->addParameterInformation('blockTitle', \Change\Documents\Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_title', $ucf));
		$this->addParameterInformation('toDisplayDocumentIds', \Change\Documents\Property::TYPE_DOCUMENTARRAY, true)
			->setAllowedModelsNames('Rbs_Media_File')
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_to_display_document_ids', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Media', 'file-thumbnail.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.media.admin.template_file_thumbnail_label', $ucf));
		$templateInformation->addParameterInformation('itemsPerRow', \Change\Documents\Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.media.admin.block_items_per_row', $ucf))
			->setNormalizeCallback(function ($parametersValues) {
				$value = isset($parametersValues['itemsPerRow']) ? intval($parametersValues['itemsPerRow']) : 3;
				return ($value <= 1) ? 1 : (($value >= 4) ? 4 : $value);
			});
	}
}
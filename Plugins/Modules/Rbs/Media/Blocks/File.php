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
 * @name \Rbs\Media\Blocks\File
 */
class File extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('toDisplayDocumentIds');
		$parameters->addParameterMeta('blockTitle');
		$parameters->setLayoutParameters($event->getBlockLayout());
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();

		$fileIds = $parameters->getParameter('toDisplayDocumentIds');
		if (is_array($fileIds) && count($fileIds))
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$storageManager =  $event->getApplicationServices()->getStorageManager();
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			$attributes['files'] = [];
			foreach ($fileIds as $fileId)
			{
				$file = $documentManager->getDocumentInstance($fileId);
				if ($file instanceof \Rbs\Media\Documents\File)
				{
					$itemInfo = $storageManager->getItemInfo($file->getPath());
					$attributes['files'][] = [
						'document' => $file,
						'size' => $itemInfo->getSize(),
						'formattedSize' => $i18nManager->transFileSize($itemInfo->getSize())
					];
				}
			}
			return count($attributes['files']) ? 'file.twig' : null;
		}

		return null;
	}
}
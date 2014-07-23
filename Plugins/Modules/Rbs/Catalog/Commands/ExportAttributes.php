<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Catalog\Commands\ExportAttributes
 */
class ExportAttributes
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$workspace = $event->getApplication()->getWorkspace();
		$filePath = $workspace->composeAbsolutePath($event->getParam('fileName'));
		$directory = dirname($filePath);
		$file = basename($filePath);
		if (substr($file, -5) != '.json') {
			$filePath .= '.json';
		}
		if (!is_dir($directory)) {
			\Change\Stdlib\File::mkdir($directory);
		}

		$attributes = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute')->getDocuments();

		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
		$query->getPropertyModelBuilder('code', 'Rbs_Catalog_Attribute', 'collectionCode');
		$collections = $query->getDocuments();


		$export = new \Rbs\Generic\Json\Export($applicationServices->getDocumentManager());
		$export->setDocumentCodeManager($applicationServices->getDocumentCodeManager());
		$export->setContextId('Rbs Catalog Attributes');

		$export->setDocuments($attributes);
		$export->addDocuments($collections);

		$buildDocumentCode = function($document, $contextId) {
			if ($document instanceof \Rbs\Collection\Documents\Collection)
			{
				return $document->getCode();
			}
			return $document->getId();
		};
		$export->getOptions()->set('buildDocumentCode', $buildDocumentCode);

		file_put_contents($filePath, json_encode($export->toArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
		$response->addInfoMessage('Attributes exported in file: ' . $filePath);
	}
}
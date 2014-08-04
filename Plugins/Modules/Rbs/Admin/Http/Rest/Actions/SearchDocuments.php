<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Zend\Http\Response as HttpResponse;

/**
 * Returns the list of all the functions declared in the blocks.
 * Parameters:
 * - modelName (string, required)
 * - searchString (string, required)
 * - limit (integer, optional, default 10)
 * - columns (string[], optional, default [])
 * @name \Rbs\Admin\Http\Rest\Actions\SearchDocuments
 */
class SearchDocuments
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$manager = $genericServices->getAdminManager();
		}
		else
		{
			throw new \RuntimeException('GenericServices not set', 999999);
		}

		$result = new \Change\Http\Rest\V1\CollectionResult();

		$modelName = $request->getPost('modelName', $request->getQuery('modelName'));
		$searchString = $request->getPost('searchString', $request->getQuery('searchString'));
		if (!$modelName || !$searchString)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_400);
			$event->setResult($result);
			return;
		}

		$limit = $request->getPost('limit', $request->getQuery('limit', 10));
		$documents = $manager->searchDocuments($modelName, $searchString, $limit);

		$extraColumns = $event->getRequest()->getQuery('columns', array());
		$urlManager = $event->getUrlManager();

		$count = 0;
		foreach ($documents as $document)
		{
			$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumns));
			$count++;
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setLimit(intval($limit));
		$result->setSort('label');
		$result->setDesc(false);
		$result->setCount($count);
		$event->setResult($result);
	}
}
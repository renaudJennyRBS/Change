<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\DocumentList
 */
class DocumentList
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$result = $this->getNewCollectionResult($event);

		$ids = $event->getRequest()->getQuery('ids');
		if (! is_array($ids))
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_400);
			$event->setResult($result);
			return;
		}

		if (($limit = $event->getRequest()->getQuery('limit', count($ids))) !== null)
		{
			$result->setLimit(intval($limit));
		}

		/* @var $documentManager \Change\Documents\DocumentManager */
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$extraColumn = $event->getRequest()->getQuery('column', array());
		$urlManager = $event->getUrlManager();

		$count = 0;
		foreach ($ids as $id)
		{
			$document = $documentManager->getDocumentInstance($id);
			if ($document)
			{
				$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumn));
				$count++;
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setCount($count);
		$event->setResult($result);
	}


	/**
	 * @param \Change\Http\Event $event
	 * @return CollectionResult
	 */
	protected function getNewCollectionResult($event)
	{
		$result = new CollectionResult();

		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($sort = $event->getRequest()->getQuery('sort')) !== null)
		{
			$result->setSort($sort);
			if (($desc = $event->getRequest()->getQuery('desc')) !== null)
			{
				$result->setDesc($desc);
				return $result;
			}
		}
		else
		{
			$result->setSort('id');
			$result->setDesc(true);
		}
		return $result;
	}

}
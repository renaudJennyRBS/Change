<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Http\Rest;

/**
* @name \Rbs\Generic\Http\Rest\DocumentCodeContextExist
*/
class DocumentCodeContextExist
{
	public function execute(\Change\Http\Event $event)
	{
		$documentCodeManager = $event->getApplicationServices()->getDocumentCodeManager();
		$context = $event->getRequest()->getPost('context');
		if ($context)
		{
			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setArray(['result' => $documentCodeManager->contextExist($context)]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$event->setResult($result);
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999, 'context param is missing');
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			$event->setResult($result);
		}
	}
} 
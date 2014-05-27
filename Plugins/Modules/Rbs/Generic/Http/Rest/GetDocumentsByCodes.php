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
* @name \Rbs\Generic\Http\Rest\GetDocumentsByCodes
*/
class GetDocumentsByCodes
{
	public function execute(\Change\Http\Event $event)
	{
		$documentCodeManager = $event->getApplicationServices()->getDocumentCodeManager();
		$context = $event->getRequest()->getPost('context');
		$codes = $event->getRequest()->getPost('codes');
		if ($context && is_array($codes))
		{
			$documents = [];
			foreach ($codes as $key => $documentCode)
			{
				$documentsFromCode = $documentCodeManager->getDocumentsByCode($documentCode, $context);
				if (isset($documentsFromCode[0]) && $documentsFromCode[0])
				{
					$documents[$key] = $documentsFromCode[0]->getId();
				}
			}

			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setArray($documents);
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
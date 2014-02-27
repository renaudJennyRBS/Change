<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Http\Result;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\DeleteDocument
 */
class DeleteDocument
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$document = $this->getDocument($event);
		if ($document === null)
		{
			//Document Not Found
			return;
		}

		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$document->delete();
			$result = new Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument|null
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			return null;
		}
		return $document;
	}
}

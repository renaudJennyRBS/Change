<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Result;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\DeleteLocalizedDocument
 */
class DeleteLocalizedDocument
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return Localizable|\Change\Documents\AbstractDocument|null
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized())
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			return null;
		}

		if (!($document instanceof Localizable))
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		return $document;
	}

	/**
	 * Use Required Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$LCID = $event->getParam('LCID');
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \RuntimeException('Invalid Parameter: LCID', 71000);
		}

		$document = $this->getDocument($event);
		if ($document === null)
		{
			//Document Not Found
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$documentManager->pushLCID($LCID);

			/* @var $document Localizable */
			$document->deleteCurrentLocalization();

			$result = new Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);

			$documentManager->popLCID();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}

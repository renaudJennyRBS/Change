<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Resources;

use Change\Documents\Interfaces\Editable;
use Change\Http\Rest\V1\ErrorResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\Resources\CreateDocument
 */
class CreateDocument
{
	/**
	 * Use Required Event Params: modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$documentId = $event->getParam('documentId');
		if ($documentId !== null)
		{
			$documentId = intval($documentId);
			if ($documentId <= 0)
			{
				throw new \RuntimeException('Invalid Parameter: documentId', 71000);
			}

			$document = $documentManager->getDocumentInstance($documentId);
			if ($document)
			{
				$errorResult = new ErrorResult('DOCUMENT-ALREADY-EXIST', 'document already exist', HttpResponse::STATUS_CODE_409);
				$errorResult->setData(array('document-id' => $documentId));
				$errorResult->addDataValue('model-name', $document->getDocumentModelName());
				$event->setResult($errorResult);
				return;
			}
		}

		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$document = $documentManager->getNewDocumentInstanceByModel($model);
		if ($documentId)
		{
			$document->initialize($documentId);
		}

		$document->useCorrection($event->getApplication()->getConfiguration()->getEntry('Change/Http/Rest/useCorrection'));

		$properties = $event->getRequest()->getPost()->toArray();

		$LCID = isset($properties['refLCID']) ? strval($properties['refLCID']) : $event->getApplicationServices()
			->getI18nManager()->getLCID();
		if (!$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			$supported = $event->getApplicationServices()->getI18nManager()->getSupportedLCIDs();
			$errorResult = new ErrorResult('INVALID-LCID', 'Invalid refLCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $LCID);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
			return;
		}
		$event->setParam('LCID', $LCID);

		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		$pop = false;
		try
		{
			$documentManager->pushLCID($LCID);
			$pop = true;
			$transactionManager->begin();
			$result = $document->populateDocumentFromRestEvent($event);
			if ($result)
			{
				if ($document instanceof Editable)
				{
					$document->setOwnerUser($event->getAuthenticationManager()->getCurrentUser());
				}
				$this->create($event, $document, $properties);
			}
			$transactionManager->commit();
			$documentManager->popLCID();
		}
		catch (\Exception $e)
		{
			if ($pop)
			{
				$documentManager->popLCID();
			}
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $properties
	 * @throws \Exception
	 */
	protected function create($event, $document, $properties)
	{
		try
		{
			$document->create();
			$event->setParam('documentId', $document->getId());

			$getDocument = new GetDocument();
			$getDocument->execute($event);

			$result = $event->getResult();
			if ($result instanceof DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
			}
		}
		catch (\Change\Documents\PropertiesValidationException $e)
		{
			$errors = $e->getPropertiesErrors();
			$errorResult = new ErrorResult('VALIDATION-ERROR', 'Document properties validation error', HttpResponse::STATUS_CODE_409);
			if (count($errors) > 0)
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$pe = array();
				foreach ($errors as $propertyName => $errorsMsg)
				{
					foreach ($errorsMsg as $errorMsg)
					{
						$pe[$propertyName][] = $i18nManager->trans($errorMsg);
					}
				}
				$errorResult->addDataValue('properties-errors', $pe);
			}
			$event->setResult($errorResult);
			return;
		}
	}
}

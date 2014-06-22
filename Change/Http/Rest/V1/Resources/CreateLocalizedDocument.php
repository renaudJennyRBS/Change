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
use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\V1\ErrorResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\Resources\CreateLocalizedDocument
 */
class CreateLocalizedDocument
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized())
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		if ($documentId <= 0)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$document = $documentManager->getDocumentInstance($documentId, $model);
		if ($document === null)
		{
			$document = $documentManager->getDocumentInstance($documentId);
			if ($document !== null)
			{
				throw new \RuntimeException('Invalid Parameter: documentId', 71000);
			}

			$document = $documentManager->getNewDocumentInstanceByModel($model);
			$document->initialize($documentId);
		}

		$document->useCorrection($event->getApplication()->getConfiguration()->getEntry('Change/Http/Rest/useCorrection'));

		if (!($document instanceof Localizable))
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		$LCID = $event->getParam('LCID');

		$properties = $event->getRequest()->getPost()->toArray();
		if (isset($properties['LCID']))
		{
			if ($LCID === null)
			{
				$LCID = $properties['LCID'];
			}
			elseif ($LCID !== $properties['LCID'])
			{
				$LCID = null;
			}
		}

		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			$supported = $event->getApplicationServices()->getI18nManager()->getSupportedLCIDs();
			$errorResult = new ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $LCID);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
			return;
		}
		$event->setParam('LCID', $LCID);

		/* @var $document \Change\Documents\AbstractDocument|Localizable|Editable */
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		$pop = false;
		try
		{
			$documentManager->pushLCID($LCID);
			$pop = true;
			$transactionManager->begin();
			if (!$document->getCurrentLocalization()->isNew())
			{
				$definedLCIDArray = $document->getLCIDArray();
				$supported = array_values(array_diff($event->getApplicationServices()->getI18nManager()->getSupportedLCIDs(),
					$definedLCIDArray));
				$errorResult = new ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('value', $LCID);
				$errorResult->addDataValue('supported-LCID', $supported);
				$event->setResult($errorResult);
			}
			else
			{
				$result = $document->populateDocumentFromRestEvent($event);
				if ($result)
				{
					if ($document instanceof Editable)
					{
						$document->setOwnerUser($event->getAuthenticationManager()->getCurrentUser());
					}
					$this->create($event, $document, $properties);
				}
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
	 * @return \Change\Http\Rest\V1\Resources\DocumentResult
	 */
	protected function create($event, $document, $properties)
	{
		/* @var $document \Change\Documents\AbstractDocument|Localizable */
		try
		{
			$document->save();
			$event->setParam('LCID', $document->getCurrentLCID());

			$getLocalizedDocument = new GetLocalizedDocument();
			$getLocalizedDocument->execute($event);

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

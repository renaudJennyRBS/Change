<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;
use Change\Documents\Interfaces\Localizable;

/**
 * @name \Change\Http\Rest\Actions\CreateLocalizedDocument
 */
class CreateLocalizedDocument
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized())
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		if ($documentId <= 0)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if ($document === null)
		{
			$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
			if ($document !== null)
			{
				throw new \RuntimeException('Invalid Parameter: documentId', 71000);
			}

			$document = $event->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModel($model);
			$document->initialize($documentId);
		}

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
			elseif($LCID !== $properties['LCID'])
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

		/* @var $document \Change\Documents\AbstractDocument */
		$documentManager = $document->getDocumentServices()->getDocumentManager();
		$documentManager->pushLCID($LCID);

		if ($document->isNew())
		{
			$event->setParam('LCID', $LCID);
			$this->create($event, $document, $properties);
		}
		else
		{
			/* @var $document Localizable */
			$definedLCIDArray = $document->getLocalizableFunctions()->getLCIDArray();
			$supported = array_values(array_diff($event->getApplicationServices()->getI18nManager()->getSupportedLCIDs(), $definedLCIDArray));
			$errorResult = new ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $LCID);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
		}
		$documentManager->popLCID();
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument|Localizable  $document
	 * @param array $properties
	 * @throws \Exception
	 * @return DocumentResult
	 */
	protected function create($event, $document, $properties)
	{
		$urlManager = $event->getUrlManager();
		foreach ($document->getDocumentModel()->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if (array_key_exists($name, $properties))
			{
				try
				{
					$c = new PropertyConverter($document, $property, $urlManager);
					$c->setPropertyValue($properties[$name]);
				}
				catch (\Exception $e)
				{
					$errorResult = new ErrorResult('INVALID-VALUE-TYPE', 'Invalid property value type', HttpResponse::STATUS_CODE_409);
					$errorResult->setData(array('name' => $name, 'value' => $properties[$name], 'type' => $property->getType()));
					$errorResult->addDataValue('document-type', $property->getDocumentType());
					$event->setResult($errorResult);
					return;
				}
			}
		}

		try
		{
			$document->create();
			$event->setParam('LCID', $document->getLCID());

			$getLocalizedDocument = new GetLocalizedDocument();
			$getLocalizedDocument->execute($event);

			$result = $event->getResult();
			if ($result instanceof DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
			}
		}
		catch (\Exception $e)
		{
			$code = $e->getCode();
			if ($code && $code >= 52000 && $code < 53000)
			{
				$errors = isset($e->propertiesErrors) ? $e->propertiesErrors : array();
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
			throw $e;
		}
	}
}

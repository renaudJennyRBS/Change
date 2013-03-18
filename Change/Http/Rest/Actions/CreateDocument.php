<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\CreateDocument
 */
class CreateDocument
{
	/**
	 * Use Required Event Params: modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$documentId = $event->getParam('documentId');
		if ($documentId !== null)
		{
			$documentId = intval($documentId);
			if ($documentId <= 0)
			{
				throw new \RuntimeException('Invalid Parameter: documentId', 71000);
			}

			$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
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
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}


		$document = $event->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModel($model);
		if ($documentId)
		{
			$document->initialize($documentId);
		}
		$properties = $event->getRequest()->getPost()->toArray();

		if ($document instanceof Localizable)
		{
			$LCID = isset($properties['refLCID']) ? strval($properties['refLCID']) : null;
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
		}
		else
		{
			$LCID = null;
		}

		if ($LCID)
		{
			$documentManager = $document->getDocumentServices()->getDocumentManager();
			$documentManager->pushLCID($LCID);
			$this->create($event, $document, $properties);
			$documentManager->popLCID();
		}
		else
		{
			$this->create($event, $document, $properties);
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
			$redirect = $event->getParam('documentId') === null;
			$document->create();
			$event->setParam('documentId', $document->getId());

			$getDocument = new GetDocument();
			$getDocument->execute($event);

			if ($redirect && (($result = $event->getResult()) instanceof DocumentResult))
			{
				/* @var $result DocumentResult */
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
				$selfLinks = $result->getLinks()->getByRel('self');
				if ($selfLinks && $selfLinks[0] instanceof DocumentLink)
				{
					/* @var $sl DocumentLink */
					$sl = $selfLinks[0];
					$href = $sl->href();
					$result->setHeaderLocation($href);
					$result->setHeaderContentLocation($href);
				}
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

<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\UpdateLocalizedDocument
 */
class UpdateLocalizedDocument
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return Localizable|\Change\Documents\AbstractDocument|null
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized())
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
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
	 */
	public function execute($event)
	{
		$LCID = $event->getParam('LCID');
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \RuntimeException('Invalid Parameter: LCID', 71000);
		}

		$document = $this->getDocument($event);
		if (!$document)
		{
			//Document Not Found
			return;
		}

		$properties = $event->getRequest()->getPost()->toArray();
		if (isset($properties['LCID']) && $properties['LCID'] != $LCID)
		{
			$supported = array($LCID);
			$errorResult = new ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $properties['LCID']);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
			return;
		}

		$documentManager = $document->getDocumentServices()->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);
			if (!$document->isNew())
			{
				$this->update($event, $document, $properties);
			}
			else
			{
				/* @var $document Localizable */
				$supported = $document->getLocalizableFunctions()->getLCIDArray();
				$errorResult = new ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('value', $LCID);
				$errorResult->addDataValue('supported-LCID', $supported);
				$event->setResult($errorResult);
			}
			$documentManager->popLCID();
		}
		catch (\Exception $e)
		{
			$documentManager->popLCID($e);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $properties
	 * @throws \Exception
	 */
	protected function update($event, $document, $properties)
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
			$document->update();

			$getDocument = new GetLocalizedDocument();
			$getDocument->execute($event);
			$result = $event->getResult();
			if ($result instanceof DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
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

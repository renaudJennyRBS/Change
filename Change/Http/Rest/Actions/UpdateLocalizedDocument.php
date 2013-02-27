<?php
namespace Change\Http\Rest\Actions;

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
	 * @return \Change\Documents\Interfaces\Localizable|\Change\Documents\AbstractDocument
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
		if (!$document || !($document instanceof \Change\Documents\Interfaces\Localizable))
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
		$properties = $event->getRequest()->getPost()->toArray();
		if (isset($properties['LCID']) && $properties['LCID'] != $LCID)
		{
			$supported = array($LCID);
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $properties['LCID']);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
			return;
		}

		$documentManager = $document->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);
			if (!$document->isNew())
			{
				$this->update($event, $document, $properties);
			}
			else
			{
				$supported = $document->getLocalizableFunctions()->getLCIDArray();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('value', $LCID);
				$errorResult->addDataValue('supported-LCID', $supported);
				$event->setResult($errorResult);
			}
			$document->getDocumentManager()->popLCID();
		}
		catch (\Exception $e)
		{
			$document->getDocumentManager()->popLCID($e);
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
					$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-VALUE-TYPE', 'Invalid property value type', HttpResponse::STATUS_CODE_409);
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
		}
		catch (\Exception $e)
		{
			$code = $e->getCode();
			if ($code && $code >= 52000 && $code < 53000)
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('VALIDATION-ERROR', 'Document properties validation error', HttpResponse::STATUS_CODE_409);
				if (count($errors = $document->getPropertiesErrors()) > 0)
				{
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

		$getDocument = new GetLocalizedDocument();
		$getDocument->execute($event);
		$result = $event->getResult();
		if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		}
	}
}

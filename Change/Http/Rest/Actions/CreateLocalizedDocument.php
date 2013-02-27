<?php
namespace Change\Http\Rest\Actions;

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
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document || !($document instanceof Localizable))
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		$properties = $event->getRequest()->getPost()->toArray();
		$LCID = isset($properties['LCID']) ? strval($properties['LCID']) : null;
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			$supported = $event->getApplicationServices()->getI18nManager()->getSupportedLCIDs();
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $LCID);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
			return;
		}

		$document->getDocumentManager()->pushLCID($LCID);

		if ($document->isNew())
		{
			$this->create($event, $document, $properties);
		}
		else
		{
			$definedLCIDArray = $document->getLocalizableFunctions()->getLCIDArray();
			$supported = array_values(array_diff($event->getApplicationServices()->getI18nManager()->getSupportedLCIDs(), $definedLCIDArray));
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-LCID', 'Invalid LCID property value', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', $LCID);
			$errorResult->addDataValue('supported-LCID', $supported);
			$event->setResult($errorResult);
		}
		$document->getDocumentManager()->popLCID();
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $properties
	 * @return \Change\Http\Rest\Result\DocumentResult
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
			$document->create();
			$getDocument = new GetDocument();
			$getDocument->execute($event);

			$result = $event->getResult();
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);

				foreach ($result->getLinks() as $link)
				{
					if ($link instanceof \Change\Http\Rest\Result\DocumentLink && $link->getRel() === 'self')
					{
						$href = $link->href();
						$result->setHeaderLocation($href);
						$result->setHeaderContentLocation($href);
					}
				}
			}
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
	}
}

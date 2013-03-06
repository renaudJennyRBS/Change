<?php
namespace Change\Http\Rest\Actions;

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
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$document = $event->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModel($model);
		$properties = $event->getRequest()->getPost()->toArray();

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$LCID = isset($properties['refLCID']) ? strval($properties['refLCID']) : null;
			if (!$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				$supported = $event->getApplicationServices()->getI18nManager()->getSupportedLCIDs();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-LCID', 'Invalid refLCID property value', HttpResponse::STATUS_CODE_409);
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
			$event->setParam('documentId', $document->getId());
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

		$getDocument = new GetDocument();
		$getDocument->execute($event);
		$result = $event->getResult();
		if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
			$selfLinks = $result->getLinks()->getByRel('self');
			if ($selfLinks && $selfLinks[0] instanceof \Change\Http\Rest\Result\DocumentLink)
			{
				$href = $selfLinks[0]->href();
				$result->setHeaderLocation($href);
				$result->setHeaderContentLocation($href);
			}
		}
	}
}

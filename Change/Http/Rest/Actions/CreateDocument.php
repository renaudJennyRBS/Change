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
				$supported = implode(', ', $event->getApplicationServices()->getI18nManager()->getSupportedLCIDs());
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-REFLCID', 'Invalid refLCID property ('.$supported.'): ' . $LCID);
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
			$document->getDocumentManager()->pushLCID($LCID);
			$this->create($event, $document, $properties);
			$document->getDocumentManager()->popLCID();
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
	 */
	protected function create($event, $document, $properties)
	{
		$urlManager = $event->getUrlManager();
		foreach ($document->getDocumentModel()->getProperties() as $name => $property)
		{
			try
			{
				/* @var $property \Change\Documents\Property */
				if (array_key_exists($name, $properties))
				{
					$c = new PropertyConverter($document, $property, $urlManager);
					$c->setPropertyValue($properties[$name]);
				}
			}
			catch (\Exception $e)
			{
				$msg = $document->getDocumentModel() . '::' . $property . ': '. $e->getMessage();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('POPULATE-ERROR', $msg);
				$event->setResult($errorResult);
				return;
			}
		}

		try
		{
			$document->create();
			$event->setParam('documentId', $document->getId());

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
		catch (\Exception $e)
		{
			$msg = $document->getDocumentModel(). ': '. $e->getMessage();
			if (count($document->getPropertiesErrors()))
			{
				$msg .= " (" . implode(', ', array_keys($document->getPropertiesErrors())) . ")";
			}
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('CREATE-ERROR', $msg);
			$event->setResult($errorResult);
			return;
		}
	}
}

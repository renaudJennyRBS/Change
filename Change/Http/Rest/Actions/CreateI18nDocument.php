<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\CreateI18nDocument
 */
class CreateI18nDocument
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$documentId = $event->getParam('documentId');
		if (!$documentId)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			//Document Not Found
			return;
		}

		$properties = $event->getRequest()->getPost()->toArray();

		$LCID = isset($properties['LCID']) ? strval($properties['LCID']) : null;
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			$msg = $document->getDocumentModel(). ' Invalid LCID: ' . $LCID;
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('CREATE-ERROR', $msg);
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
			$msg = $document->getDocumentModel(). ' already exist in LCID: ' . $LCID;
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('CREATE-ERROR', $msg);
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

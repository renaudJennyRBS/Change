<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\UpdateDocument
 */
class UpdateI18nDocument
{
	/**
	 * Use Required Event Params: documentId, modelName, LCID
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

		$LCID = $event->getParam('LCID');
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \RuntimeException('Invalid Parameter: LCID', 71000);
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
		elseif(!($document instanceof \Change\Documents\Interfaces\Localizable))
		{
			throw new \RuntimeException('Invalid Parameter: LCID', 71000);
		}

		$properties = $event->getRequest()->getPost()->toArray();
		if (isset($properties['LCID']) && $properties['LCID'] != $LCID)
		{
			$exception = new \RuntimeException('Invalid Parameter: LCID', 71000);
			$exception->httpStatusCode = HttpResponse::STATUS_CODE_400;
			throw $exception;
		}

		/* @var $document \Change\Documents\AbstractDocument */
		$documentManager = $document->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);
			$this->update($event, $document, $properties);
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
	 */
	protected function update($event, $document, $properties)
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
			$document->update();

			$getDocument = new GetI18nDocument();
			$getDocument->execute($event);
			$result = $event->getResult();
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			}
		}
		catch (\Exception $e)
		{
			$msg = $document->getDocumentModel(). ': '. $e->getMessage();
			if (count($document->getPropertiesErrors()))
			{
				$msg .= " (" . implode(', ', array_keys($document->getPropertiesErrors())) . ")";
			}
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('UPDATE-ERROR', $msg);
			$event->setResult($errorResult);
			return;
		}
	}
}

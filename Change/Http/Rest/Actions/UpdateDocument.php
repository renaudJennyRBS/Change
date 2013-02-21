<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\UpdateDocument
 */
class UpdateDocument
{
	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$documentId = $event->getParam('documentId');
		if (!$documentId)
		{
			return;
		}

		$modelName = $event->getParam('modelName');
		if ($modelName)
		{
			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				return;
			}
		}
		else
		{
			return;
		}

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			return;
		}
		$LCID = $event->getParam('LCID');
		if (!$LCID && $document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$LCID = $document->getRefLCID();
		}
		$properties = $event->getRequest()->getPost()->toArray();

		if ($LCID)
		{
			if ($event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				$document->getDocumentManager()->pushLCID($LCID);
				$this->update($event, $document, $properties);
				$document->getDocumentManager()->popLCID();
			}
		}
		else
		{
			$this->update($event, $document, $properties);
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

			$getDocument = new GetDocument();
			$getDocument->execute($event);
			$result = $event->getResult();
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			}

			/*
			$result = new \Change\Http\Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);
			*/
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

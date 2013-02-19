<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\DeleteDocument
 */
class DeleteDocument
{
	/**
	 * Use Event Params: documentId, modelName
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

		try
		{
			$document->delete();
			$result = new \Change\Http\Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);
		}
		catch (\Exception $e)
		{
			$msg = $document . ': '. $e->getMessage();
			$errorResult = new \Change\Http\Rest\Result\ErrorResult('DELETE-ERROR', $msg);
			$event->setResult($errorResult);
			return;
		}

	}
}

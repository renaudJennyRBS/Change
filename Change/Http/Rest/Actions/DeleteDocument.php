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

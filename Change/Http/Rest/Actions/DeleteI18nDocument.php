<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\DeleteI18nDocument
 */
class DeleteI18nDocument
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

		$LCID = $event->getParam('LCID');
		if (!$LCID)
		{
			return;
		}

		$modelName = $event->getParam('modelName');
		if ($modelName)
		{
			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if (!$model || !$model->isLocalized())
			{
				return;
			}
		}
		else
		{
			return;
		}

		$documentManager = $event->getDocumentServices()->getDocumentManager();

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$documentManager->pushLCID($LCID);

			try
			{

				$document->deleteLocalized();
				$result = new \Change\Http\Result();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
				$event->setResult($result);
			}
			catch (\Exception $e)
			{
				$msg = $document . '('. $LCID .'): '. $e->getMessage();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('DELETE-ERROR', $msg);
				$event->setResult($errorResult);
			}

			$documentManager->popLCID();
		}
	}
}

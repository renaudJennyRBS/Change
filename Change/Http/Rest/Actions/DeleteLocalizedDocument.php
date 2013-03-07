<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\DeleteLocalizedDocument
 */
class DeleteLocalizedDocument
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
		$documentManager = $document->getDocumentServices()->getDocumentManager();

		try
		{
			$documentManager->pushLCID($LCID);
			$document->getLocalizableFunctions()->delete();

			$result = new \Change\Http\Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);

			$documentManager->popLCID();
		}
		catch (\Exception $e)
		{
			$documentManager->popLCID($e);
		}
	}
}

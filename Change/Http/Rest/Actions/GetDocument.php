<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetDocument
 */
class GetDocument
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument|null
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			return null;
		}

		$documentId = intval($event->getParam('documentId'));
		return $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
	}

	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$document = $this->getDocument($event);
		if ($document === null)
		{
			//Document Not Found
			return;
		}

		if ($document instanceof Localizable)
		{
			$event->setParam('LCID', $document->getRefLCID());
			$getLocalizedDocument = new GetLocalizedDocument();
			$getLocalizedDocument->execute($event);
			return;
		}
		$this->generateResult($event, $document);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @return DocumentResult
	 */
	protected function generateResult($event, $document)
	{
		$urlManager = $event->getUrlManager();

		$result = new DocumentResult($urlManager, $document);
		$event->setResult($result);
		/* @var $documentLink DocumentLink */
		$documentLink = $result->getRelLink('self')[0];

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$currentUrl = $urlManager->getSelf()->normalize()->toString();
		if (($href = $documentLink->href()) != $currentUrl)
		{
			$result->setHeaderContentLocation($href);
		}
		return $result;
	}
}

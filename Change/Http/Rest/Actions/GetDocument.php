<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\GetDocument
 */
class GetDocument
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
		elseif ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$event->setParam('LCID', $document->getRefLCID());
			$setI18nDocument = new GetI18nDocument();

			$setI18nDocument->execute($event);
			$result = $event->getResult();
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_303);
				$selfLinks = $result->getLinks()->getByRel('self');
				if ($selfLinks && $selfLinks[0] instanceof \Change\Http\Rest\Result\DocumentLink)
				{
					$href = $selfLinks[0]->href();
					$result->setHeaderLocation($href);
					$result->setHeaderContentLocation($href);
				}
			}
			return;
		}

		$this->generateResult($event, $document);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $document)
	{
		$urlManager = $event->getUrlManager();
		$result = new \Change\Http\Rest\Result\DocumentResult();
		$result->setHeaderLastModified($document->getModificationDate());

		$documentLink = new \Change\Http\Rest\Result\DocumentLink($urlManager, $document);
		$result->addLink($documentLink);

		$model = $document->getDocumentModel();

		$properties = array();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			$c = new PropertyConverter($document, $property, $urlManager);
			$properties[$name] = $c->getRestValue();
		}

		$result->setProperties($properties);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}

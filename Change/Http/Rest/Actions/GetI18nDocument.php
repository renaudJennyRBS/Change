<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\GetI18nDocument
 */
class GetI18nDocument
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

		/* @var $document \Change\Documents\AbstractDocument */
		$documentManager = $document->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);

			$this->generateResult($event, $document);

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

		/* @var $document \Change\Documents\Interfaces\Localizable */

		$i18n = array();
		foreach ($document->getLCIDArray() as $tmpLCID)
		{
			$LCIDLink = clone($documentLink);
			$LCIDLink->setLCID($tmpLCID);
			$i18n[$tmpLCID] = $LCIDLink->href();
		}
		$result->setI18n($i18n);


		$result->setProperties($properties);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}

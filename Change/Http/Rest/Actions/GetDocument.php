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
			$model = null;
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

		if ($LCID)
		{
			if ($event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				$document->getDocumentManager()->pushLCID($LCID);
				if (!$document->isNew())
				{
					$this->generateResult($event, $document);
				}
				$document->getDocumentManager()->popLCID();
			}
		}
		else
		{
			$this->generateResult($event, $document);
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

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$i18n = array();
			foreach ($document->getLCIDArray() as $tmpLCID)
			{
				$LCIDLink = clone($documentLink);
				$LCIDLink->setLCID($tmpLCID);
				$i18n[$tmpLCID] = $LCIDLink->href();
			}
			$result->setI18n($i18n);
		}
		$result->setProperties($properties);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}

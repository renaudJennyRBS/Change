<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetDocument
 */
class GetDocument
{
	/**
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
	 * @return \Change\Http\Rest\DocumentResult
	 */
	protected function generateResult($event, $document)
	{
		$urlManager = $event->getUrlManager();
		$result = new \Change\Http\Rest\DocumentResult();
		$documentLink = new \Change\Http\Rest\DocumentLink($document);

		$links = array($documentLink->toSelfLinkArray($urlManager));

		$model = $document->getDocumentModel();

		$properties = array();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			$value = $property->getValue($document);
			if ($value instanceof \DateTime)
			{
				$properties[$name] = $value->format(\DateTime::ISO8601);
			}
			elseif ($value instanceof \Change\Documents\AbstractDocument)
			{
				$dl = new \Change\Http\Rest\DocumentLink($value);
				$properties[$name] = $dl->toPropertyLinkArray($urlManager);
			}
			elseif (is_array($value))
			{
				foreach ($value as $doc)
				{
					/* @var $doc \Change\Documents\AbstractDocument */
					$dl = new \Change\Http\Rest\DocumentLink($doc);
					$properties[$name][] = $dl->toPropertyLinkArray($urlManager);
				}
			}
			else
			{
				$properties[$name] = $value;
			}
		}

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$i18n = array();
			foreach ($document->getLCIDArray() as $tmpLCID)
			{
				$documentLink->setLCID($tmpLCID);
				$i18n[$tmpLCID] = $urlManager->getByPathInfo($documentLink->getPathInfo())->toString();
			}
			$result->setI18n($i18n);
		}

		$result->setLinks($links);
		$result->setProperties($properties);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}

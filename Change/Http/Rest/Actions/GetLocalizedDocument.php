<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\DocumentResult;
use Change\Logging\Logging;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;

/**
 * @name \Change\Http\Rest\Actions\GetLocalizedDocument
 */
class GetLocalizedDocument
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return Localizable|\Change\Documents\AbstractDocument|null
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
		if (!$document)
		{
			return null;
		}

		if (!($document instanceof Localizable))
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
		if ($document === null)
		{
			//Document Not Found
			return;
		}

		$documentManager = $document->getDocumentServices()->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);

			$this->generateResult($event, $document, $LCID);

			$documentManager->popLCID();
		}
		catch (\Exception $e)
		{
			$documentManager->popLCID($e);
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param Logging $logging
	 * @return null|string
	 */
	protected function buildEtag($document, Logging $logging = null)
	{
		$parts = array($document->getModificationDate()->format(\DateTime::ISO8601), $document->getTreeName());

		if ($document->getDocumentModel()->useCorrection() && $document->getCorrectionFunctions()->hasCorrection())
		{
			$parts[] = $document->getCorrectionFunctions()->getCorrection()->getStatus();
		}

		if ($document instanceof Editable)
		{
			$parts[] = $document->getDocumentVersion();
		}

		if ($document instanceof Publishable)
		{
			$parts[] = $document->getPublicationStatus();
		}

		if ($document instanceof Localizable)
		{
			$parts = array_merge($parts, $document->getLocalizableFunctions()->getLCIDArray());
		}

		if ($logging)
		{
			$logging->info('ETAG BUILD INFO: ' . implode(',', $parts));
		}
		return md5(implode(',', $parts));
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param Localizable | \Change\Documents\AbstractDocument  $document
	 * @param string $LCID
	 * @return DocumentResult
	 */
	protected function generateResult($event, $document, $LCID)
	{
		$urlManager = $event->getUrlManager();

		$result = new DocumentResult();
		$result->setHeaderEtag($this->buildEtag($document, $event->getApplicationServices()->getLogging()));

		$documentLink = new DocumentLink($urlManager, $document);
		$result->addLink($documentLink);
		if ($document->getTreeName())
		{
			$tn = $document->getDocumentServices()->getTreeManager()->getNodeByDocument($document);
			if ($tn)
			{
				$l = new TreeNodeLink($urlManager, $tn, TreeNodeLink::MODE_LINK);
				$l->setRel('node');
				$result->addLink($l);
			}
		}

		$model = $document->getDocumentModel();

		$properties = array();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			$c = new PropertyConverter($document, $property, $urlManager);
			$properties[$name] = $c->getRestValue();
		}

		$result->setProperties($properties);

		$this->addActions($result, $document, $urlManager, $LCID);

		$i18n = array();

		/* @var $document Localizable */
		foreach ($document->getLocalizableFunctions()->getLCIDArray() as $tmpLCID)
		{
			$LCIDLink = clone($documentLink);
			$LCIDLink->setLCID($tmpLCID);
			$i18n[$tmpLCID] = $LCIDLink->href();
		}
		$result->setI18n($i18n);

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param DocumentResult $result
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 * @param string $LCID
	 */
	protected function addActions($result, $document, $urlManager, $LCID)
	{
		if ($document->getDocumentModel()->useCorrection())
		{
			if ($document->getCorrectionFunctions()->hasCorrection())
			{
				$correction = $document->getCorrectionFunctions()->getCorrection();
				if ($correction)
				{
					$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
					$result->addAction($l);

					if ($correction->getStatus() === Correction::STATUS_DRAFT)
					{
						$l = new DocumentActionLink($urlManager, $document, 'startCorrectionValidation');
						$result->addAction($l);
					}
					elseif ($correction->getStatus() === Correction::STATUS_VALIDATION)
					{
						$l = new DocumentActionLink($urlManager, $document, 'startCorrectionPublication');
						$result->addAction($l);
					}
				}
			}
		}

		if ($document instanceof Publishable)
		{
			$pf = $document->getPublishableFunctions();

			/* @var $document \Change\Documents\AbstractDocument */
			if ($pf->canStartValidation())
			{
				$l = new DocumentActionLink($urlManager, $document, 'startValidation');
				$result->addAction($l);
			}

			if ($pf->canStartPublication())
			{
				$l = new DocumentActionLink($urlManager, $document, 'startPublication');
				$result->addAction($l);
			}

			if ($pf->canActivate())
			{
				$l = new DocumentActionLink($urlManager, $document, 'activate');
				$result->addAction($l);
			}

			if ($pf->canDeactivate())
			{
				$l = new DocumentActionLink($urlManager, $document, 'deactivate');
				$result->addAction($l);
			}
		}
	}
}

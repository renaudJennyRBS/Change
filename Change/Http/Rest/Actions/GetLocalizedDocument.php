<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Correction;
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
use Change\Http\Rest\Result\ModelLink;

/**
 * @name \Change\Http\Rest\Actions\GetLocalizedDocument
 */
class GetLocalizedDocument
{
	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return Localizable|AbstractDocument|null
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

		$documentManager = $event->getDocumentServices()->getDocumentManager();
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
	 * @param \Change\Http\Result $result
	 * @param AbstractDocument $document
	 */
	protected function setResultCacheHeader($result, $document)
	{
		if ($document->getDocumentModel()->isStateless())
		{
			return;
		}

		$etagParts = array($document->getModificationDate()->format(\DateTime::ISO8601), $document->getTreeName());
		if ($document instanceof Correction && $document->hasCorrection())
		{
			$etagParts[] = $document->getCurrentCorrection()->getStatus();
		}
		if ($document instanceof Editable)
		{
			$etagParts[] = $document->getDocumentVersion();
		}

		if ($document instanceof Publishable)
		{
			$etagParts[] = $document->getPublicationStatus();;
		}

		if ($document instanceof Localizable)
		{
			$etagParts = array_merge($etagParts, $document->getLCIDArray());
		}
		$result->setHeaderEtag(md5(implode(',', $etagParts)));
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param AbstractDocument  $document
	 * @param string $LCID
	 * @return DocumentResult
	 */
	protected function generateResult($event, $document, $LCID)
	{
		$urlManager = $event->getUrlManager();
		$result = new DocumentResult();
		$documentLink = new DocumentLink($urlManager, $document);
		$result->addLink($documentLink);

		$modelLink = new ModelLink($urlManager, array('name' => $document->getDocumentModelName()), false);
		$modelLink->setRel('model');
		$result->addLink($modelLink);

		if ($document->getTreeName())
		{
			$tn = $event->getDocumentServices()->getTreeManager()->getNodeByDocument($document);
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

		$event->setResult($result);
		$documentEvent = new \Change\Documents\Events\Event('updateRestResult', $document, array('restResult' => $result));
		$document->getEventManager()->trigger($documentEvent);

		$i18n = array();
		/* @var $document AbstractDocument|Localizable */
		foreach ($document->getLCIDArray() as $tmpLCID)
		{
			$LCIDLink = clone($documentLink);
			$LCIDLink->setLCID($tmpLCID);
			$i18n[$tmpLCID] = $LCIDLink->href();
		}
		$result->setI18n($i18n);

		$currentUrl = $urlManager->getSelf()->normalize()->toString();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		if (($href = $documentLink->href()) != $currentUrl)
		{
			$result->setHeaderContentLocation($href);
		}
		else
		{
			$this->setResultCacheHeader($result, $document);
		}
		return $result;
	}

	/**
	 * @param DocumentResult $result
	 * @param AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 * @param string $LCID
	 */
	protected function addActions($result, $document, $urlManager, $LCID)
	{
		if ($document instanceof Correction)
		{
			/* @var $document AbstractDocument|Correction */
			$correction = $document->getCurrentCorrection();
			if ($correction)
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$result->addAction($l);

				if ($correction->isDraft())
				{
					$l = new DocumentActionLink($urlManager, $document, 'startCorrectionValidation');
					$result->addAction($l);
				}
				elseif ($correction->inValidation())
				{
					$l = new DocumentActionLink($urlManager, $document, 'startCorrectionPublication');
					$result->addAction($l);
				}
			}
		}

		if ($document instanceof Publishable)
		{
			/* @var $document AbstractDocument|Publishable */
			if ($document->canStartValidation())
			{
				$l = new DocumentActionLink($urlManager, $document, 'startValidation');
				$result->addAction($l);
			}

			if ($document->canStartPublication())
			{
				$l = new DocumentActionLink($urlManager, $document, 'startPublication');
				$result->addAction($l);
			}

			if ($document->canActivate())
			{
				$l = new DocumentActionLink($urlManager, $document, 'activate');
				$result->addAction($l);
			}

			if ($document->canDeactivate())
			{
				$l = new DocumentActionLink($urlManager, $document, 'deactivate');
				$result->addAction($l);
			}
		}
	}
}

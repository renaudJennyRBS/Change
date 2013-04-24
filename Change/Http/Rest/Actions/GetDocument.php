<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\ModelLink;
use Change\Logging\Logging;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\TreeNodeLink;

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
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			return null;
		}

		$documentId = intval($event->getParam('documentId'));
		return $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
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

			$result = $event->getResult();
			if ($result instanceof DocumentResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_303);
			}

			return;
		}
		$this->generateResult($event, $document);
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @return DocumentResult
	 */
	protected function generateResult($event, $document)
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
		$currentUrl = $urlManager->getSelf()->normalize()->toString();
		$this->addActions($result, $document, $urlManager);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		if (($href = $documentLink->href()) != $currentUrl)
		{
			$result->setHeaderContentLocation($href);
		}
		else
		{
			if (!$document->getDocumentModel()->isStateless())
			{
				$result->setHeaderEtag($this->buildEtag($document, $event->getApplicationServices()->getLogging()));
			}
		}
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param DocumentResult $result
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 */
	protected function addActions($result, $document, $urlManager)
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

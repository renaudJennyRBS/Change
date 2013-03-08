<?php
namespace Change\Http\Rest\Actions;

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
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}
		return $document;
	}

	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$document = $this->getDocument($event);
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$event->setParam('LCID', $document->getRefLCID());

			$setI18nDocument = new GetLocalizedDocument();
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Logging\Logging $logging
	 * @return null|string
	 */
	protected function buildEtag($document, \Change\Logging\Logging $logging = null)
	{
		$parts = array($document->getModificationDate()->format(\DateTime::ISO8601), $document->getTreeName());

		if ($document->getDocumentModel()->useCorrection() && $document->getCorrectionFunctions()->hasCorrection())
		{
			$parts[] = $document->getCorrectionFunctions()->getCorrection()->getStatus();
		}

		if ($document instanceof \Change\Documents\Interfaces\Editable)
		{
			$parts[] = $document->getDocumentVersion();
		}

		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$parts[] = $document->getPublicationStatus();
		}

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
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
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $document)
	{
		$urlManager = $event->getUrlManager();

		$result = new \Change\Http\Rest\Result\DocumentResult();
		//$result->setHeaderLastModified($document->getModificationDate());
		$result->setHeaderEtag($this->buildEtag($document, $event->getApplicationServices()->getLogging()));

		$documentLink = new \Change\Http\Rest\Result\DocumentLink($urlManager, $document);
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
		$this->addActions($result, $document, $urlManager);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $result
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

					if ($correction->getStatus() === \Change\Documents\Correction::STATUS_DRAFT)
					{
						$l = new DocumentActionLink($urlManager, $document, 'startCorrectionValidation');
						$result->addAction($l);
					}
					elseif ($correction->getStatus() === \Change\Documents\Correction::STATUS_VALIDATION)
					{
						$l = new DocumentActionLink($urlManager, $document, 'startCorrectionPublication');
						$result->addAction($l);
					}
				}
			}
		}

		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$pf = $document->getPublishableFunctions();
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

<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Change\Http\Rest\Actions\GetLocalizedDocument
 */
class GetLocalizedDocument
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

		$documentManager = $document->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);

			$this->generateResult($event, $document, $LCID);

			$document->getDocumentManager()->popLCID();
		}
		catch (\Exception $e)
		{
			$document->getDocumentManager()->popLCID($e);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\Interfaces\Localizable | \Change\Documents\AbstractDocument  $document
	 * @param string $LCID
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $document, $LCID)
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

		$this->addActions($result, $document, $urlManager, $LCID);

		$i18n = array();

		/* @var $document \Change\Documents\Interfaces\Localizable */
		foreach ($document->getLocalizableFunctions()->getLCIDArray() as $tmpLCID)
		{
			$LCIDLink = clone($documentLink);
			$LCIDLink->setLCID($tmpLCID);
			$i18n[$tmpLCID] = $LCIDLink->href();
		}
		$result->setI18n($i18n);

		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $result
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 * @param string $LCID
	 */
	protected function addActions($result, $document, $urlManager, $LCID)
	{
		if ($document->getDocumentModel()->useCorrection())
		{
			if ($document->hasCorrection($LCID))
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$result->addAction($l);
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

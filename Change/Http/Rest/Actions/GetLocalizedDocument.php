<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Zend\Http\Response as HttpResponse;

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
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized())
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
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

		$documentManager = $event->getApplicationServices()->getDocumentManager();
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
	 * Use Required Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function getNewLocalizedDocument($event)
	{
		$documentId = intval($event->getParam('documentId'));
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getApplicationServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model || !$model->isLocalized() || $documentId >= 0)
		{
			return;
		}

		$LCID = $event->getParam('LCID');
		if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \RuntimeException('Invalid Parameter: LCID', 71000);
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		try
		{
			$documentManager->pushLCID($LCID);
			$document = $documentManager->getNewDocumentInstanceByModel($model);
			$document->initialize($event->getParam('documentId'));
			$model->setPropertyValue($document, 'refLCID', $LCID);
			$model->setPropertyValue($document, 'modificationDate', null);
			$this->generateResult($event, $document, $LCID);
			$documentManager->popLCID();
		}
		catch (\Exception $e)
		{
			$documentManager->popLCID($e);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param AbstractDocument $document
	 * @param string $LCID
	 * @return DocumentResult
	 */
	protected function generateResult($event, $document, $LCID)
	{
		$urlManager = $event->getUrlManager();
		$result = new DocumentResult($urlManager, $document);
		$event->setResult($result);

		/* @var $documentLink DocumentLink */
		$documentLink = $result->getRelLink('self')[0];

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
		return $result;
	}

	/**
	 * @param DocumentResult $result
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 */
	protected function addCorrection($result, $document, $urlManager)
	{
		if ($document->getDocumentModel()->useCorrection())
		{
			/* @var $document \Change\Documents\Interfaces\Correction|\Change\Documents\AbstractDocument */
			$correction = $document->getCurrentCorrection();
			if ($correction)
			{
				$l = new DocumentActionLink($urlManager, $document, 'correction');
				$result->addAction($l);
			}
		}
	}
}

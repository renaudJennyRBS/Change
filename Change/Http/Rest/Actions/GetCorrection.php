<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\DocumentCorrectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetCorrection
 */
class GetCorrection
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getDocument($event)
	{
		$documentId = intval($event->getParam('documentId'));
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			return null;
		}

		if (!$document->getDocumentModel()->useCorrection())
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}
		return $document;
	}

	/**
	 * Use Required Event Params: documentId
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$document = $this->getDocument($event);
		if (!$document)
		{
			//Document Not Found
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$LCID = null;
		if ($document instanceof Localizable)
		{
			$LCID = $event->getParam('LCID');
			if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				throw new \RuntimeException('Invalid Parameter: LCID', 71000);
			}
		}

		/* @var $document \Change\Documents\Interfaces\Correction */
		if ($LCID)
		{
			try
			{
				$documentManager->pushLCID($LCID);
				if ($document->hasCorrection())
				{
					$this->doGetCorrection($event, $document, $document->getCurrentCorrection());
				}
				$documentManager->popLCID();
			}
			catch (\Exception $e)
			{
				$documentManager->popLCID($e);
			}
		}
		else
		{
			if ($document->hasCorrection())
			{
				$correction = $document->getCurrentCorrection();
				$this->doGetCorrection($event, $document, $correction);
			}
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Correction $correction
	 * @throws \Exception
	 */
	protected function doGetCorrection($event, $document, $correction)
	{
		$urlManager = $event->getUrlManager();
		$result = new DocumentCorrectionResult($event->getUrlManager(), $document);

		$documentLink = new DocumentLink($urlManager, $document);
		$documentLink->setRel('resource');
		$result->addLink($documentLink);

		$model = $document->getDocumentModel();
		$correctionInfos = array('id' => $correction->getId(), 'status' => $correction->getStatus(),
			'propertiesNames' => array());
		$properties = array();
		if ($document instanceof Localizable)
		{
			$localizedOnly = \Change\Documents\Correction::NULL_LCID_KEY != $correction->getLCID();
		}
		else
		{
			$localizedOnly = false;
		}
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized() || !$localizedOnly)
			{
				$c = new PropertyConverter($document, $property, null, $urlManager);
				if ($correction->isModifiedProperty($name))
				{
					$correctionInfos['propertiesNames'][] = $name;
					$properties[$name] = $c->convertToRestValue($correction->getPropertyValue($name));
				}
				else
				{
					$properties[$name] = $c->getRestValue();
				}

				if ($name === 'creationDate')
				{
					$correctionInfos['creationDate'] = $c->convertToRestValue($correction->getCreationDate());
					$correctionInfos['publicationDate'] = $c->convertToRestValue($correction->getPublicationDate());
				}
			}
		}
		$result->setCorrectionInfos($correctionInfos);
		$result->setProperties($properties);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
	}
}

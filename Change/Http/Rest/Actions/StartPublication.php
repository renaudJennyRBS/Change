<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\StartPublication
 */
class StartPublication
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return AbstractDocument|Publishable|null
	 */
	protected function getDocument($event)
	{
		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			return null;
		}

		if (!($document instanceof Publishable))
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
		$documentManager = $document->getDocumentServices()->getDocumentManager();
		
		$LCID = null;
		if ($document instanceof Localizable)
		{
			$LCID = $event->getParam('LCID');
			if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				throw new \RuntimeException('Invalid Parameter: LCID', 71000);
			}
		}

		if ($LCID)
		{
			try
			{
				$documentManager->pushLCID($LCID);
				if ($document->isNew())
				{
					throw new \RuntimeException('Invalid Parameter: LCID', 71000);
				}
				$this->doStartPublication($event, $document);
				$documentManager->popLCID();
			}
			catch (\Exception $e)
			{
				$documentManager->popLCID($e);
			}
		}
		else
		{
			$this->doStartPublication($event, $document);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param Publishable $document
	 * @param  $document
	 * @throws \Exception
	 */
	protected function doStartPublication($event, $document)
	{
		$oldStatus = $document->getPublicationStatus();
		try
		{
			/* @var $document AbstractDocument|Publishable */
			$document->startPublication();
			$result = new ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

			$l = new DocumentLink($event->getUrlManager(), $document);
			$l->setRel('resource');

			$result->setArray(array('link' => $l->toArray(), 'data' =>
			array('old-publication-status' => $oldStatus, 'new-publication-status' => $document->getPublicationStatus())));

			$event->setResult($result);

		}
		catch (\Exception $e)
		{
			$code = $e->getCode();
			if ($code && $code == 55000)
			{
				$errorResult = new ErrorResult('PUBLICATION-ERROR', 'Invalid Publication status', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('old-publication-status', $oldStatus);
				$event->setResult($errorResult);
				return;
			}
			throw $e;
		}
	}
}

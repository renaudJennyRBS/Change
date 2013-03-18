<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\StartCorrectionValidation
 */
class StartCorrectionValidation
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument|null
	 */
	protected function getDocument($event)
	{
		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			return null;
		}

		if (!($document->getDocumentModel()->useCorrection()))
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

		$publicationDate = $event->getRequest()->getQuery('publicationDate');
		if ($publicationDate)
		{
			$publicationDate = \DateTime::createFromFormat(\DateTime::ISO8601, strval($publicationDate));
			if ($publicationDate === false)
			{
				throw new \RuntimeException('Invalid Parameter: publicationDate', 71000);
			}
		}
		else
		{
			$publicationDate = null;
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
				$this->doStartValidation($event, $document, $publicationDate);
				$documentManager->popLCID();
			}
			catch (\Exception $e)
			{
				$documentManager->popLCID($e);
			}
		}
		else
		{
			$this->doStartValidation($event, $document, $publicationDate);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \DateTime $publicationDate
	 * @throws \Exception
	 */
	protected function doStartValidation($event, $document, $publicationDate)
	{
		try
		{
			$correction = $document->getCorrectionFunctions()->startValidation($publicationDate);
			if ($correction)
			{
				$result = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$l = new DocumentLink($event->getUrlManager(), $document);
				$l->setRel('resource');
				$result->setArray(array('link' => $l->toArray(),
					'data' => array('correction-id' => $correction->getId(),'correction-status' => $correction->getStatus())));
				$event->setResult($result);
			}
		}
		catch (\Exception $e)
		{
			$code = $e->getCode();
			if ($code && $code == 55000)
			{
				$errorResult = new ErrorResult('PUBLICATION-ERROR', 'Invalid Publication status', HttpResponse::STATUS_CODE_409);
				$event->setResult($errorResult);
				return;
			}
			throw $e;
		}
	}
}

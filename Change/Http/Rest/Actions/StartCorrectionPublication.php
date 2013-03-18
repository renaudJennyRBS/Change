<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\StartCorrectionPublication
 */
class StartCorrectionPublication
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
			return;
		}

		$LCID = null;
		if ($document instanceof Localizable)
		{
			$LCID = $event->getParam('LCID');
			if (!$LCID || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
			{
				throw new \RuntimeException('Invalid Parameter: LCID', 71000);
			}
		}

		$publishImmediately = $event->getRequest()->getQuery('publishImmediately');
		if ($publishImmediately)
		{
			if ($publishImmediately === 'true')
			{
				$publishImmediately = true;
			}
			elseif ($publishImmediately === 'false')
			{
				$publishImmediately = false;
			}
			else
			{
				throw new \RuntimeException('Invalid Parameter: publishImmediately', 71000);
			}
		}
		else
		{
			$publishImmediately = false;
		}

		if ($LCID)
		{
			$documentManager = $document->getDocumentServices()->getDocumentManager();
			try
			{

				$documentManager->pushLCID($LCID);
				if ($document->isNew())
				{
					throw new \RuntimeException('Invalid Parameter: LCID', 71000);
				}
				$this->doStartPublication($event, $document, $publishImmediately);
				$documentManager->popLCID();
			}
			catch (\Exception $e)
			{
				$documentManager->popLCID($e);
			}
		}
		else
		{
			$this->doStartPublication($event, $document, $publishImmediately);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param boolean $publishImmediately
	 * @throws \Exception
	 */
	protected function doStartPublication($event, $document, $publishImmediately)
	{
		try
		{
			$correction = $document->getCorrectionFunctions()->startPublication();
			if ($correction)
			{
				if ($publishImmediately && ($correction->getPublicationDate() <= new \DateTime()))
				{
					$document->getCorrectionFunctions()->publish();
				}

				$result = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$l = new DocumentLink($event->getUrlManager(), $document);
				$l->setRel('resource');
				$result->setArray(array('link' => $l->toArray(),
					'data' => array('correction-id' => $correction->getId(),
						'correction-status' => $correction->getStatus())));
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

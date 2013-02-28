<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\StartValidation
 */
class StartValidation
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable
	 */
	protected function getDocument($event)
	{
		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}

		if (!($document instanceof \Change\Documents\Interfaces\Publishable))
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
		$documentManager = $document->getDocumentManager();

		$LCID = null;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
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
				$this->doStartValidation($event, $document);
				$documentManager->popLCID();
			}
			catch (\Exception $e)
			{
				$documentManager->popLCID($e);
			}
		}
		else
		{
			$this->doStartValidation($event, $document);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\Interfaces\Publishable $document
	 * @throws \Exception
	 */
	protected function doStartValidation($event, $document)
	{
		$oldStatus = $document->getPublicationStatus();
		try
		{
			$document->getPublishableFunctions()->startValidation();
			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

			$l = new \Change\Http\Rest\Result\DocumentLink($event->getUrlManager(), $document);
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
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('PUBLICATION-ERROR', 'Invalid Publication status', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('old-publication-status', $oldStatus);
				$event->setResult($errorResult);
				return;
			}
			throw $e;
		}
	}
}

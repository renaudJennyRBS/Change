<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

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
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
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
				if ($document->getCorrectionFunctions()->hasCorrection())
				{
					$this->doGetCorrection($event, $document, $document->getCorrectionFunctions()->getCorrection());
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
			if ($document->getCorrectionFunctions()->hasCorrection())
			{
				$correction = $document->getCorrectionFunctions()->getCorrection();
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
		$result = new \Change\Http\Rest\Result\DocumentResult();

		$documentLink = new \Change\Http\Rest\Result\DocumentLink($urlManager, $document);
		$documentLink->setRel('resource');
		$result->addLink($documentLink);

		$model = $document->getDocumentModel();

		$properties = array('__correction' => array('id' => $correction->getId(), 'status' => $correction->getStatus(), 'propertiesNames' => array()));
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			$c = new PropertyConverter($document, $property, $urlManager);
			if ($correction->isModifiedProperty($name))
			{
				$properties['__correction']['propertiesNames'][] = $name;
				$properties[$name] = $c->convertToRestValue($correction->getPropertyValue($name));
			}
			else
			{
				$properties[$name] = $c->getRestValue();
			}

			if ($name === 'creationDate')
			{
				$properties['__correction']['creationDate'] = $c->convertToRestValue($correction->getCreationDate());
				$properties['__correction']['publicationDate'] = $c->convertToRestValue($correction->getPublicationDate());
			}
		}

		$result->setProperties($properties);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}

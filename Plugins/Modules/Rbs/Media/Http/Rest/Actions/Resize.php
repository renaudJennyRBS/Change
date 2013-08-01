<?php
namespace Rbs\Media\Http\Rest\Actions;

use Change\Http\Result;
use Change\Http\Event;
use Zend\Http\Response;

/**
 * @name \Rbs\Media\Http\Rest\Actions\Resize
 */
class Resize
{

	public function resize(Event $event)
	{
		$maxWidth = $event->getRequest()->getQuery('maxWidth', 0);
		$maxHeight = $event->getRequest()->getQuery('maxHeight', 0);
		$documentId = $event->getParam('documentId');
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if (!($document instanceof \Rbs\Media\Documents\Image))
		{
			$event->setResult(new Result(Response::STATUS_CODE_404));
			return;
		}
		$result = new Result(Response::STATUS_CODE_301);
		$result->setHeaderLocation($document->getPublicURL($maxWidth, $maxHeight));
		$event->setResult($result);
	}
}
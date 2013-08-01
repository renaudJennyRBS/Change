<?php
namespace Change\Http\Web\Actions;

use Change\Http\Result;
use Change\Http\Web\Event;
use Rbs\Media\Std\GDResizer;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\GetImagestorageItemContent
 */
class GetImagestorageItemContent
{
	/**
	 * Use Required Event Params: changeURI
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $changeURI \Zend\Uri\Uri */
		$changeURI = $event->getParam('changeURI');
		/* @var $originalURI \Zend\Uri\Uri */
		$originalURI = $event->getParam('originalURI');
		$maxWidth = $event->getParam('maxWidth', 0);
		$maxHeight = $event->getParam('maxHeight', 0);
		if (!($changeURI instanceof \Zend\Uri\Uri) || !($originalURI instanceof \Zend\Uri\Uri) || !file_exists($originalURI->toString()))
		{
			$event->setResult(new Result(HttpResponse::STATUS_CODE_404));
			return;
		}
		if ($maxWidth == 0 && $maxHeight == 0)
		{
			$event->setParam('changeURI', $originalURI);
		}
		else if (!file_exists($changeURI->toString()))
		{
			$resizer = new GDResizer();
			$resizer->resize($originalURI->toString(), $changeURI->toString(), $maxWidth, $maxHeight);
		}
		$forwardedAction = new GetStorageItemContent();
		$forwardedAction->execute($event);
	}
}
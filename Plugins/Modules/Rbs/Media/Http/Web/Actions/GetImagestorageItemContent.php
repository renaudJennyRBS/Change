<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Http\Web\Actions;

use Change\Http\Result;
use Change\Http\Web\Actions\GetStorageItemContent;
use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Media\Http\Web\Actions\GetImagestorageItemContent
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
		if (!($changeURI instanceof \Zend\Uri\Uri) || !($originalURI instanceof \Zend\Uri\Uri))
		{
			$event->setResult(new Result(HttpResponse::STATUS_CODE_404));
			return;
		}

		$originalPath = $originalURI->toString();
		$path = $changeURI->toString();
		if (!file_exists($originalPath))
		{
			$event->setResult(new Result(HttpResponse::STATUS_CODE_404));
			return;
		}

		if (!file_exists($path) || filemtime($path) < filemtime($originalPath))
		{
			$event->getApplication()->getLogging()->info(__METHOD__ . ' resize to ' . $path);
			if ($maxWidth == 0 && $maxHeight == 0)
			{
				copy($originalPath, $path);
			}
			else
			{
				$resizer = new \Change\Presentation\Images\Resizer();
				$resizer->resize($originalPath, $path, $maxWidth, $maxHeight);
			}
		}
		$forwardedAction = new GetStorageItemContent();
		$forwardedAction->execute($event);
	}
}
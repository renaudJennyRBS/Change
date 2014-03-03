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
use Rbs\Media\Std\Resizer;
use Zend\Http\Response as HttpResponse;

/**
 * @name \CRbs\Media\Http\Web\Actions\GetImagestorageItemContent
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
		if (!file_exists($changeURI->toString()))
		{
			if ($maxWidth == 0 && $maxHeight == 0)
			{
				copy($originalURI->toString(), $changeURI->toString());
			}
			else
			{
				$resizer = new Resizer();
				$resizer->resize($originalURI->toString(), $changeURI->toString(), $maxWidth, $maxHeight);
			}
		}
		$forwardedAction = new GetStorageItemContent();
		$forwardedAction->execute($event);
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\BlockResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetBlockInformation
 */
class GetBlockInformation
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$blockName = $event->getParam('blockName');
		if ($blockName)
		{
			$this->generateResult($event, $blockName);
		}
		return;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $blockName
	 */
	protected function generateResult($event, $blockName)
	{
		$bm = $event->getApplicationServices()->getBlockManager();
		$info = $bm->getBlockInformation($blockName);
		if ($info)
		{
			$result = new BlockResult($event->getUrlManager(), $info);
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}

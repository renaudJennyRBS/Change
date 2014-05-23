<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Blocks;

use Change\Http\Rest\V1\CollectionResult;
use Change\Http\Rest\V1\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\Blocks\GetBlockCollection
 */
class GetBlockCollection
{
	/**
	 * Use Event Params: vendor, shortModuleName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$vendor = $event->getParam('vendor');
		$shortModuleName = $event->getParam('shortModuleName');
		if ($vendor && $shortModuleName)
		{
			$this->generateResult($event, $vendor, $shortModuleName);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $vendor
	 * @param string $shortModuleName
	 */
	protected function generateResult($event, $vendor, $shortModuleName)
	{
		$bm = $event->getApplicationServices()->getBlockManager();
		$shortBlocksName = array();
		foreach ($bm->getBlockNames() as $name)
		{
			$a = explode('_', $name);
			if ($a[0] === $vendor && $a[1] === $shortModuleName)
			{
				$shortBlocksName[] = $name;
			}
		}

		if (!count($shortBlocksName))
		{
			return;
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$result->setOffset(0);
		$basePath = $event->getRequest()->getPath();
		$selfLink = new Link($urlManager, $basePath);
		$result->addLink($selfLink);
		$result->setCount(count($shortBlocksName));
		$result->setSort(null);

		foreach ($shortBlocksName as $name)
		{
			$info = $bm->getBlockInformation($name);
			$l = new BlockLink($urlManager, $info);
			$result->addResource($l);
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
	}
}

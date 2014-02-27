<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Admin\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAvailablePageFunctions($event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$pageId = $event->getParam('pageId');
			$functions = $applicationServices->getPageManager()->getFunctions();
			if (!\Change\Stdlib\String::isEmpty($pageId))
			{
				$page = $applicationServices->getDocumentManager()->getDocumentInstance($pageId);

				$parsedFunctions = array();
				if ($page instanceof \Rbs\Website\Documents\FunctionalPage)
				{
					$blocks = $page->getContentLayout()->getBlocks();
					if (count($blocks))
					{
						foreach ($blocks as $block)
						{
							$blockName = $block->getName();
							foreach ($functions as $function)
							{
								if (isset($function['block']))
								{
									if (is_array($function['block']) && in_array($blockName, $function['block']))
									{
										$parsedFunctions[$function['code']] = $function['label'];
									}
									elseif (is_string($function['block']) && $function['block'] == $blockName)
									{
										$parsedFunctions[$function['code']] = $function['label'];
									}
								}
							}
						}
					}
				}

				$event->setParam('collection', new CollectionArray('Rbs_Website_AvailablePageFunctions', $parsedFunctions));
				$event->stopPropagation();
			}
			else
			{
				$parsedFunctions = array();
				foreach ($functions as $function)
				{
					$parsedFunctions[$function['code']] = $function['label'];
				}
				$event->setParam('collection', new CollectionArray('Rbs_Website_AvailablePageFunctions', $parsedFunctions));
				$event->stopPropagation();
			}
		}
	}
}
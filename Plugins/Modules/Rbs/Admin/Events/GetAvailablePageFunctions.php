<?php

namespace Rbs\Admin\Events;

use Change\Collection\CollectionArray;
use Change\Documents\DocumentServices;

/**
 * @name \Rbs\Admin\Events\GetAvailablePageFunctions
 */
class GetAvailablePageFunctions
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function execute($event)
	{
		$code = $event->getParam('code');
		if ($code !== 'Rbs_Website_AvailablePageFunctions')
		{
			return;
		}

		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof DocumentServices)
		{
			$pageId = $event->getParam('pageId');
			$page = $documentServices->getDocumentManager()->getDocumentInstance($pageId);

			if ($page instanceof \Rbs\Website\Documents\FunctionalPage)
			{
				$parsedFunctions = array();
				$blocks = $page->getContentLayout()->getBlocks();
				if (count($blocks))
				{
					$presentationServices = new \Change\Presentation\PresentationServices($documentServices->getApplicationServices());
					$blockManager = $presentationServices->getBlockManager();
					foreach ($blocks as $block)
					{
						$blockInfo = $blockManager->getBlockInformation($block->getName());
						if ($blockInfo)
						{
							foreach ($blockInfo->getFunctions() as $name => $label)
							{
								$parsedFunctions[$name] = $label;
							}
						}
					}
				}

				$event->setParam('collection', new CollectionArray($code, $parsedFunctions));
				$event->stopPropagation();
			}
			else
			{
				$presentationServices = new \Change\Presentation\PresentationServices($documentServices->getApplicationServices());
				$blockManager = $presentationServices->getBlockManager();
				$parsedFunctions = array();
				foreach ($blockManager->getBlockNames() as $blockName)
				{
					$blockInfo = $blockManager->getBlockInformation($blockName);
					if ($blockInfo)
					{
						foreach ($blockInfo->getFunctions() as $name => $label)
						{
							$parsedFunctions[$name] = $label;
						}
					}
				}

				$event->setParam('collection', new CollectionArray($code, $parsedFunctions));
				$event->stopPropagation();
			}
		}
	}
}
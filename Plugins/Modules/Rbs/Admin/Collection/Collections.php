<?php
namespace Rbs\Admin\Collection;

use Change\Collection\CollectionArray;
use Change\Documents\DocumentServices;
use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Admin\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAvailablePageFunctions($event)
	{
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
					$presentationServices = new PresentationServices($documentServices->getApplicationServices());
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

				$event->setParam('collection', new CollectionArray('Rbs_Website_AvailablePageFunctions', $parsedFunctions));
				$event->stopPropagation();
			}
			else
			{
				$presentationServices = new PresentationServices($documentServices->getApplicationServices());
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

				$event->setParam('collection', new CollectionArray('Rbs_Website_AvailablePageFunctions', $parsedFunctions));
				$event->stopPropagation();
			}
		}
	}
}
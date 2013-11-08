<?php
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
			$page = $applicationServices->getDocumentManager()->getDocumentInstance($pageId);

			if ($page instanceof \Rbs\Website\Documents\FunctionalPage)
			{
				$parsedFunctions = array();
				$blocks = $page->getContentLayout()->getBlocks();
				if (count($blocks))
				{
					$blockManager = $applicationServices->getBlockManager();
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
				$blockManager = $applicationServices->getBlockManager();
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
<?php
namespace Change\Website\Blocks;

use Change\Presentation\Blocks\BlockManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Change\Website\Blocks\SharedListenerAggregate
 */
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		//For Runtime Execution
		$identifiers = array('Change_Website_Richtext');
		$callBack = function ($event)
		{
			$o = new Richtext();
			$o->onConfiguration($event);
		};
		$events->attach($identifiers, array(BlockManager::EVENT_PARAMETERS), $callBack, 5);

		$callBack = function ($event)
		{
			$o = new Richtext();
			$o->onExecute($event);
		};
		$events->attach($identifiers, array(BlockManager::EVENT_EXECUTE), $callBack, 5);

		$identifiers = array('Change_Website_Menu');
		$callBack = function ($event)
		{
			$o = new Menu();
			$o->onConfiguration($event);
		};
		$events->attach($identifiers, array(BlockManager::EVENT_PARAMETERS), $callBack, 5);

		$callBack = function ($event)
		{
			$o = new Menu();
			$o->onExecute($event);
		};
		$events->attach($identifiers, array(BlockManager::EVENT_EXECUTE), $callBack, 5);

		//For backoffice edition
		$callBack = function ($event)
		{
			if ($event instanceof \Zend\EventManager\Event)
			{
				$blockManager = $event->getTarget();
				if ($blockManager instanceof BlockManager)
				{
					$blockManager->registerBlock('Change_Website_Richtext', function () use ($blockManager)
					{
						return new BlocksInformation('Change_Website_Richtext', $blockManager);
					});
					$blockManager->registerBlock('Change_Website_Menu', function () use ($blockManager)
					{
						return new BlocksInformation('Change_Website_Menu', $blockManager);
					});
				}
			}
		};
		$events->attach(BlockManager::DEFAULT_IDENTIFIER, array(BlockManager::EVENT_INFORMATION), $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
	}
}

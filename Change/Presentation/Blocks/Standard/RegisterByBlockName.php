<?php
namespace Change\Presentation\Blocks\Standard;

use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;
use Zend\EventManager\EventManagerInterface;

/**
 * @api
 * Class RegisterByBlockName
 * @package Change\Presentation\Blocks\Standard
 * @name \Change\Presentation\Blocks\Standard\RegisterByBlockName
 */
class RegisterByBlockName
{

	/**
	 * example \Change\Website\Blocks\Menu
	 * @var string
	 */
	protected $blockName;

	/**
	 * @var boolean
	 */
	protected $hasInformation;

	/**
	 * @api
	 * @param string $blockName
	 * @param boolean $hasInformation
	 * @param EventManagerInterface $events
	 */
	function __construct($blockName, $hasInformation = true, EventManagerInterface $events = null)
	{
		$this->blockName = $blockName;
		$this->hasInformation = (bool)$hasInformation;
		if ($events)
		{
			$this->attach($events, $this->hasInformation);
		}
	}

	/**
	 * example: \Change\Website\Blocks\Menu
	 * @return string
	 */
	protected function getClassName()
	{
		$names = explode('_', $this->blockName);
		if (count($names) === 3)
		{
			$classParts = array('', $names[0], $names[1], 'Blocks', $names[2]);
			return implode('\\', $classParts);
		}
		return null;
	}

	/**
	 * @param EventManagerInterface $events
	 * @param boolean $hasInformation
	 */
	protected function attach(EventManagerInterface $events, $hasInformation = true)
	{
		$className = $this->getClassName();
		$callBack = function ($event) use ($className)
		{
			if (class_exists($className))
			{
				$o = new $className();
				$onParameters = array($o, 'onParameterize');
				if (is_callable($onParameters))
				{
					call_user_func($onParameters, $event);
				}
				else
				{
					new \LogicException('Method ' . $className . '->onParameterize($event) not defined', 999999);
				}
			}
			else
			{
				new \LogicException('Class ' . $className . ' not found', 999999);
			}
		};
		$events->attach(BlockManager::composeEventName(BlockManager::EVENT_PARAMETERIZE, $this->blockName), $callBack, 5);

		$callBack = function ($event) use ($className)
		{
			$o = new $className();
			$onExecute = array($o, 'onExecute');
			if (is_callable($onExecute))
			{
				call_user_func($onExecute, $event);
			}
			else
			{
				new \LogicException('Method ' . $className . '->onExecute($event) not defined', 999999);
			}
		};
		$events->attach(BlockManager::composeEventName(BlockManager::EVENT_EXECUTE, $this->blockName), $callBack, 5);

		if (!$hasInformation)
		{
			return;
		}

		//For backoffice edition
		$className .= 'Information';
		$blockName = $this->blockName;
		$callBack = function (\Change\Events\Event $event) use ($className, $blockName)
		{
			$blockManager = $event->getTarget();
			if ($blockManager instanceof BlockManager)
			{
				$blockManager->registerBlock($blockName, function () use ($blockName, $className, $blockManager, $event)
				{
					if (class_exists($className))
					{
						$class = new $className($blockName, $blockManager);
						if ($class instanceof Information)
						{
							$class->onInformation($event);
							return $class;
						}
					}
					if ($event->getApplicationServices())
					{
						$event->getApplicationServices()->getLogging()->error('Block Information class ' . $className . ' not found');
					}
					return null;
				});
			}
		};

		$events->attach(BlockManager::EVENT_INFORMATION, $callBack, 5);
	}
}
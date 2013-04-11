<?php
namespace Change\Http\Web\Blocks;

use Zend\EventManager\EventManager;

/**
 * @name \Change\Http\Web\Blocks\Manager
 */
class Manager
{
	const DEFAULT_IDENTIFIER = 'Http.Web.Block';

	const EVENT_PARAMETERS = 'block.configuration';

	const EVENT_EXECUTE = 'block.execute';

	/**
	 * @var EventManager
	 */
	protected $sharedEventManager;

	/**
	 * @param \Change\Application $application
	 */
	function __construct($application)
	{
		$this->sharedEventManager = $application->getSharedEventManager();

		$sharedListeners = $application->getConfiguration()->getEntry('Change/Http/Web/Blocks', array());
		foreach ($sharedListeners as $className)
		{
			if (class_exists($className))
			{
				$sharedListener = new $className();
				if ($sharedListener instanceof \Zend\EventManager\SharedListenerAggregateInterface)
				{
					$sharedListener->attachShared($this->sharedEventManager);
				}
			}
			else
			{
				throw new \RuntimeException('Block configuration class not found: ' . $className, 999999);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Layout\Block $blockLayout
	 * @return Parameters
	 */
	public function getParameters($blockLayout)
	{
		$eventManager = new EventManager(array(static::DEFAULT_IDENTIFIER, $blockLayout->getName()));
		$eventManager->setSharedManager($this->sharedEventManager);

		$event = new Event(static::EVENT_PARAMETERS, $this);

		$event->setBlockLayout($blockLayout);
		$results = $eventManager->trigger($event, function ($result)
		{
			return $result instanceof Parameters;
		});
		$parameters = ($results->stopped()) ? $results->last() : $event->getBlockParameters();
		return ($parameters instanceof Parameters) ? $parameters : $this->getNewParameters($blockLayout);
	}


	/**
	 * @param \Change\Http\Web\Layout\Block $blockLayout
	 * @return Parameters
	 */
	public function getNewParameters($blockLayout)
	{
		$parameters = new Parameters($blockLayout->getName());
		return $parameters;
	}

	/**
	 * @param \Change\Http\Web\Layout\Block $blockLayout
	 * @param Parameters $parameters
	 * @return Result|null
	 */
	public function getResult($blockLayout, $parameters)
	{
		$eventManager = new EventManager(array(static::DEFAULT_IDENTIFIER, $blockLayout->getName()));
		$eventManager->setSharedManager($this->sharedEventManager);

		$event = new Event(static::EVENT_EXECUTE, $this);
		$event->setBlockLayout($blockLayout);
		$event->setBlockParameters($parameters);
		$results = $eventManager->trigger($event, function ($result)
		{
			return $result instanceof Result;
		});
		$result = ($results->stopped()) ? $results->last() : $event->getBlockResult();
		return ($result instanceof Result) ? $result: null;
	}

}
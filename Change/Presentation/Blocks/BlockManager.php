<?php
namespace Change\Presentation\Blocks;

use Change\Events\SharedEventManager;
use Change\Http\Web\Result\BlockResult;
use Change\Presentation\PresentationServices;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Change\Presentation\Blocks\BlockManager
 */
class BlockManager
{
	const DEFAULT_IDENTIFIER = 'Http.Web.Block';

	const EVENT_PARAMETERS = 'block.configuration';

	const EVENT_EXECUTE = 'block.execute';

	const EVENT_INFORMATION = 'block.information';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var \Change\Documents\DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var array;
	 */
	protected $blocks;

	/**
	 * @param PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return SharedEventManager
	 * @throws \RuntimeException
	 */
	protected function registerBlocks()
	{
		if ($this->sharedEventManager === null)
		{
			$application = $this->presentationServices->getApplicationServices()->getApplication();
			$this->sharedEventManager = $application->getSharedEventManager();
			$sharedListeners = $application->getConfiguration()->getEntry('Change/Presentation/Blocks', array());

			foreach ($sharedListeners as $className)
			{
				if (class_exists($className))
				{
					$sharedListener = new $className();
					if ($sharedListener instanceof SharedListenerAggregateInterface)
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
		return $this->sharedEventManager;
	}

	/**
	 * @param $name
	 * @param Callable|null $configuration
	 */
	public function registerBlock($name, $configuration = null)
	{
		$this->blocks[$name] = $configuration;
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @param \Change\Http\Event $httpEvent
	 * @return Parameters
	 */
	public function getParameters($blockLayout, $httpEvent = null)
	{
		$sharedEventManager = $this->registerBlocks();
		$eventManager = new EventManager(array(static::DEFAULT_IDENTIFIER, $blockLayout->getName()));
		$eventManager->setSharedManager($sharedEventManager);
		$event = new Event(static::EVENT_PARAMETERS, $this);

		if ($httpEvent instanceof \Change\Http\Event)
		{
			$event->setParam('httpRequest', $httpEvent->getRequest());
			if ($this->documentServices === null)
			{
				$this->documentServices = $httpEvent->getDocumentServices();
			}
		}
		$event->setPresentationServices($this->presentationServices);
		$event->setDocumentServices($this->documentServices);
		$event->setBlockLayout($blockLayout);
		$results = $eventManager->trigger($event, function ($result)
		{
			return $result instanceof Parameters;
		});
		$parameters = ($results->stopped()) ? $results->last() : $event->getBlockParameters();
		return ($parameters instanceof Parameters) ? $parameters : $this->getNewParameters($blockLayout);
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @return Parameters
	 */
	public function getNewParameters($blockLayout)
	{
		$parameters = new Parameters($blockLayout->getName());
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @param Parameters $parameters
	 * @return BlockResult|null
	 */
	public function getResult($blockLayout, $parameters)
	{
		$sharedEventManager = $this->registerBlocks();

		$eventManager = new EventManager(array(static::DEFAULT_IDENTIFIER, $blockLayout->getName()));
		$eventManager->setSharedManager($sharedEventManager);

		$event = new Event(static::EVENT_EXECUTE, $this);
		$event->setPresentationServices($this->presentationServices);
		$event->setDocumentServices($this->documentServices);
		$event->setBlockLayout($blockLayout);
		$event->setBlockParameters($parameters);
		$results = $eventManager->trigger($event, function ($result)
		{
			return $result instanceof BlockResult;
		});
		$result = ($results->stopped()) ? $results->last() : $event->getBlockResult();
		return ($result instanceof BlockResult) ? $result : null;
	}
}
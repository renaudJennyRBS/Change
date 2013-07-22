<?php
namespace Change\Presentation\Blocks;

use Change\Documents\DocumentServices;
use Change\Events\SharedEventManager;
use Change\Http\Web\Result\BlockResult;
use Change\Presentation\PresentationServices;
use Zend\EventManager\EventManager;

/**
 * @name \Change\Presentation\Blocks\BlockManager
 */
class BlockManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'Http.Web.Block';

	const EVENT_PARAMETERIZE = 'block.parameterize';

	const EVENT_EXECUTE = 'block.execute';

	const EVENT_INFORMATION = 'block.information';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var array
	 */
	protected $blocks;

	/**
	 * @param PresentationServices $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($presentationServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}


	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param $name
	 * @param Callable|null $informationCallback
	 */
	public function registerBlock($name, $informationCallback = null)
	{
		$this->blocks[$name] = $informationCallback;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->presentationServices)
		{
			$config = $this->presentationServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/BlockManager', array());
		}
		return array();
	}

	/**
	 * @return string[]
	 */
	public function getBlockNames()
	{
		if ($this->blocks === null)
		{
			$this->blocks = array();
			$eventManager = $this->getEventManager();
			$event = new Event(static::EVENT_INFORMATION, $this);
			$eventManager->trigger($event);
		}
		return array_keys($this->blocks);
	}

	/**
	 * @param string $name
	 * @return Information|null
	 */
	public function getBlockInformation($name)
	{
		$this->getBlockNames();
		if (isset($this->blocks[$name]))
		{
			$infos = $this->blocks[$name] ;
			if ($infos instanceof Information)
			{
				return $infos;
			}
			elseif (is_callable($infos))
			{
				$infos = call_user_func($infos);
				if ($infos instanceof Information)
				{
					$this->blocks[$name] = $infos;
					return $infos;
				}
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param string $prefix
	 * @param string $blockName
	 * @return string
	 */
	public static function composeEventName($prefix, $blockName)
	{
		return $prefix . '.' . $blockName;
	}

	/**
	 * @api
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @param \Change\Http\Web\Event $httpEvent
	 * @return Parameters
	 */
	public function getParameters($blockLayout, $httpEvent)
	{
		$eventManager =  $this->getEventManager();
		$event = new Event(static::composeEventName(static::EVENT_PARAMETERIZE, $blockLayout->getName()), $this, $httpEvent->getParams());
		$event->setAuthenticationManager($httpEvent->getAuthenticationManager());
		$event->setPermissionsManager($httpEvent->getPermissionsManager());
		$event->setParam('httpRequest', $httpEvent->getRequest());
		if ($this->documentServices === null)
		{
			$this->documentServices = $httpEvent->getDocumentServices();
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
	 * @param \Change\Http\Web\Event $httpEvent
	 * @return BlockResult|null
	 */
	public function getResult($blockLayout, $parameters, $httpEvent)
	{
		$eventManager = $this->getEventManager();
		$event = new Event(static::composeEventName(static::EVENT_EXECUTE, $blockLayout->getName()), $this, $httpEvent->getParams());
		$event->setPresentationServices($this->presentationServices);
		$event->setDocumentServices($this->documentServices);
		$event->setBlockLayout($blockLayout);
		$event->setBlockParameters($parameters);
		$event->setUrlManager($httpEvent->getUrlManager());
		$results = $eventManager->trigger($event, function ($result)
		{
			return $result instanceof BlockResult;
		});
		$result = ($results->stopped()) ? $results->last() : $event->getBlockResult();
		return ($result instanceof BlockResult) ? $result : null;
	}
}
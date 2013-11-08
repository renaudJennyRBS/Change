<?php
namespace Change\Presentation\Blocks;

use Change\Http\Web\Result\BlockResult;

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

	const EVENT_GET_CACHE_ADAPTER = 'getCacheAdapter';

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var array
	 */
	protected $blocks;

	/**
	 * @var \Zend\Cache\Storage\Adapter\AbstractAdapter
	 */
	protected $cacheAdapter = false;

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 * @return $this
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->configuration;
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/BlockManager');
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
			$infos = $this->blocks[$name];
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
				else
				{
					unset($this->blocks[$name]);
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
		$eventManager = $this->getEventManager();
		$event = new Event(static::composeEventName(static::EVENT_PARAMETERIZE,
			$blockLayout->getName()), $this, $httpEvent->getParams());
		$event->setAuthenticationManager($httpEvent->getAuthenticationManager());
		$event->setPermissionsManager($httpEvent->getPermissionsManager());
		$event->setParam('httpRequest', $httpEvent->getRequest());
		$event->setBlockLayout($blockLayout);
		$event->setUrlManager($httpEvent->getUrlManager());
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
		$cacheAdapter = $this->getCacheAdapter();
		if ($cacheAdapter && ($ttl = $parameters->getTTL()) > 0)
		{
			$cacheAdapter->getOptions()->setTtl($ttl);
			$key = md5(serialize($parameters));
			if ($cacheAdapter->hasItem($key))
			{
				$result = $cacheAdapter->getItem($key);
			}
			else
			{
				$result = $this->dispatchExecute($blockLayout, $parameters, $httpEvent);
				$cacheAdapter->addItem($key, $result);
			}
			return $result;
		}
		return $this->dispatchExecute($blockLayout, $parameters, $httpEvent);
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @param Parameters $parameters
	 * @param \Change\Http\Web\Event $httpEvent
	 * @return BlockResult|null
	 */
	protected function dispatchExecute($blockLayout, $parameters, $httpEvent)
	{
		$eventManager = $this->getEventManager();
		$event = new Event(static::composeEventName(static::EVENT_EXECUTE,
			$blockLayout->getName()), $this, $httpEvent->getParams());
		$event->setBlockLayout($blockLayout);
		$event->setBlockParameters($parameters);
		$event->setUrlManager($httpEvent->getUrlManager());
		$eventManager->trigger($event);

		$result = $event->getBlockResult();
		return ($result instanceof BlockResult) ? $result : null;
	}

	/**
	 * @return \Zend\Cache\Storage\Adapter\AbstractAdapter|null
	 */
	public function getCacheAdapter()
	{
		if (false === $this->cacheAdapter)
		{
			$this->cacheAdapter = null;
			$configuration = $this->getConfiguration();
			if ($configuration->getEntry('Change/Cache/block'))
			{
				$eventManager = $this->getEventManager();
				$event = new \Change\Events\Event(static::EVENT_GET_CACHE_ADAPTER, $this);
				$eventManager->trigger($event);
				$cache = $event->getParam('cacheAdapter');
				if ($cache instanceof \Zend\Cache\Storage\Adapter\AbstractAdapter)
				{
					$this->cacheAdapter = $cache;
				}
			}
		}
		return $this->cacheAdapter;
	}

	/**
	 * @param \Zend\Cache\Storage\Adapter\AbstractAdapter $cacheAdapter
	 * @return $this
	 */
	public function setCacheAdapter(\Zend\Cache\Storage\Adapter\AbstractAdapter $cacheAdapter = null)
	{
		$this->cacheAdapter = $cacheAdapter;
		return $this;
	}
}
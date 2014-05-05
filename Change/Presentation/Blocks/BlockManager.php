<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var array
	 */
	protected $blocks;

	/**
	 * @var \Zend\Cache\Storage\Adapter\AbstractAdapter
	 */
	protected $cacheAdapter = false;

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
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
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/BlockManager');
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
	public function getParameters(\Change\Presentation\Layout\Block $blockLayout, $httpEvent)
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
	public function getResult(\Change\Presentation\Layout\Block $blockLayout, $parameters, $httpEvent)
	{
		if (!$blockLayout->getName())
		{
			return null;
		}

		$cacheAdapter = $this->getCacheAdapter();
		if ($cacheAdapter && ($ttl = $parameters->getTTL()) > 0)
		{
			$cacheAdapter->getOptions()->setTtl($ttl);
			$key = md5(serialize($parameters) . ($httpEvent ? $httpEvent->getUrlManager()->getLCID() : ''));
			if ($cacheAdapter->hasItem($key))
			{
				$result = $cacheAdapter->getItem($key);
				if ($result instanceof BlockResult){
					$result->setId($blockLayout->getId());
				}
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
		$eventName = static::composeEventName(static::EVENT_EXECUTE, $blockLayout->getName());

		$event = new Event($eventName, $this, $httpEvent->getParams());
		$attributes = new \ArrayObject(array('parameters' => $parameters, 'blockId' => $blockLayout->getId()));
		$event->setParam('attributes', $attributes);
		$event->setBlockLayout($blockLayout);
		$event->setBlockParameters($parameters);
		$event->setUrlManager($httpEvent->getUrlManager());
		$eventManager->trigger($event);

		$result = $event->getBlockResult();
		if ($result instanceof BlockResult)
		{
			$templateName = $event->getParam('templateName');
			if (!$result->hasHtml() && is_string($templateName))
			{
				$applicationServices = $event->getApplicationServices();
				$templateModuleName = $event->getParam('templateModuleName');
				if ($templateModuleName === null)
				{
					$sn = explode('_', $blockLayout->getName());
					$templateModuleName = $sn[0] . '_' . $sn[1];
				}

				$relativePath = $applicationServices->getThemeManager()->getCurrent()
					->getTemplateRelativePath($templateModuleName, 'Blocks/' . $templateName);
				$attributes = $event->getParam('attributes', $attributes);
				if ($attributes instanceof \ArrayObject)
				{
					$attributes = $attributes->getArrayCopy();
				}
				elseif (!is_array($attributes))
				{
					$attributes = [];
				}
				$templateManager = $applicationServices->getTemplateManager();
				try
				{
					$result->setHtml($templateManager->renderThemeTemplateFile($relativePath, $attributes));
				}
				catch (\Exception $e)
				{
					$error = 'Unable to render "'.$relativePath.'" template for block ' . $blockLayout->getName();
					$result->setHtml('<!-- '. $error .' -->');
					$this->getApplication()->getLogging()->error($error);
					$this->getApplication()->getLogging()->exception($e);
				}
			}
			if (!$result->hasHtml())
			{
				$result->setHtml('');
			}
			return $result;
		}
		return null;
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
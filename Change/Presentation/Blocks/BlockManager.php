<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

use Change\Http\Web\Result\BlockResult;
use Change\Presentation\Blocks\Standard\UpdateBlockInformation;

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
	 * @api
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_INFORMATION, [$this, 'onDefaultInformation']);
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
			$this->blocks = [];
			$eventManager = $this->getEventManager();
			$event = new Event(static::EVENT_INFORMATION, $this);
			$eventManager->trigger($event);
		}
		return array_keys($this->blocks);
	}

	public function onDefaultInformation(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$pluginManager = $applicationServices->getPluginManager();
		$this->registerBlocksTemplates($pluginManager);
	}

	/**
	 * @param \Change\Plugins\PluginManager $pluginManager
	 */
	protected function registerBlocksTemplates($pluginManager)
	{
		foreach ($pluginManager->getThemes() as $themePlugin)
		{
			if ($themePlugin->getActivated())
			{
				$blocksTemplatesFile = $this->getApplication()->getWorkspace()
					->composePath($themePlugin->getAbsolutePath(), 'blocks-templates.json');
				if (is_readable($blocksTemplatesFile))
				{
					$configuration = json_decode(file_get_contents($blocksTemplatesFile), true);
					if (is_array($configuration) && count($configuration))
					{
						foreach ($configuration as $blockName => $blockConfig)
						{
							if (isset($blockConfig['templates']) && is_array($blockConfig['templates'])
								&& count($blockConfig['templates'])
							)
							{
								$templates = $blockConfig['templates'];
								new UpdateBlockInformation($blockName, $this->getEventManager(),
									function ($event) use ($templates)
									{
										$this->onUpdateTemplateInformation($event, $templates);
									});
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param Event $event
	 * @param array $templatesConfiguration
	 */
	protected function onUpdateTemplateInformation(Event $event, array $templatesConfiguration)
	{
		$information = $event->getParam('information');
		if ($information instanceof \Change\Presentation\Blocks\Information)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			foreach ($templatesConfiguration as $fullyQualifiedTemplateName => $templateConfig)
			{
				$templateInformation = $information->addTemplateInformation($fullyQualifiedTemplateName);
				if (isset($templateConfig['label']))
				{
					$templateInformation->setLabel($i18nManager->trans($templateConfig['label'], ['ucf']));
				}
				else
				{
					$templateInformation->setLabel($templateInformation->getTemplateName());
				}
				if (isset($templateConfig['parameters']) && is_array($templateConfig['parameters']))
				{
					foreach ($templateConfig['parameters'] as $parameterName => $parameterConfig)
					{
						$type =
							isset($parameterConfig['type']) ? $parameterConfig['type'] : \Change\Documents\Property::TYPE_STRING;
						$required = isset($parameterConfig['required']) ? $parameterConfig['required'] === true : false;
						$defaultValue = isset($parameterConfig['defaultValue']) ? $parameterConfig['defaultValue'] : null;
						$parameter = $templateInformation->addParameterInformation($parameterName, $type, $required,
							$defaultValue);
						if (isset($parameterConfig['label']))
						{
							$parameter->setLabel($i18nManager->trans($parameterConfig['label'], ['ucf']));
						}
						else
						{
							$parameter->setLabel($parameterName);
						}
						if (isset($parameterConfig['hidden']))
						{
							$parameter->setHidden($parameterConfig['hidden'] === true);
						}
						if (isset($parameterConfig['allowedModelsNames']))
						{
							$parameter->setAllowedModelsNames($parameterConfig['allowedModelsNames']);
						}
						if (isset($parameterConfig['collectionCode']))
						{
							$parameter->setCollectionCode($parameterConfig['collectionCode']);
						}
					}
				}
			}
		}
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
					$eventManager = $this->getEventManager();
					$args = $eventManager->prepareArgs(['information' => $infos]);
					$event = new Event(static::composeEventName(static::EVENT_INFORMATION, $name), $this, $args);
					$eventManager->trigger($event);
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
				if ($result instanceof BlockResult)
				{
					$result->setId($blockLayout->getId());
					$parameters->setParameterValue('_cached', true);
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
		$attributes = new \ArrayObject(['parameters' => $parameters, 'blockId' => $blockLayout->getId()]);
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
					$error = 'Unable to render "' . $relativePath . '" template for block ' . $blockLayout->getName();
					$result->setHtml('<!-- ' . $error . ' -->');
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

	/**
	 * @param \Change\Presentation\Layout\Block[] $blocks
	 */
	public function normalizeBlocksParameters(array $blocks)
	{
		foreach ($blocks as $block)
		{
			if ($block instanceof \Change\Presentation\Layout\Block)
			{
				$blockInformation = $this->getBlockInformation($block->getName());
				if ($blockInformation instanceof Information)
				{
					$parameters = $block->getParameters();
					if (!is_array($parameters))
					{
						$parameters = [];
					}
					$parameters = $blockInformation->normalizeParameters($parameters);
					if (!is_array($parameters))
					{
						$parameters = [];
					}

					/** @var $validParameters \Change\Presentation\Blocks\ParameterInformation[] */
					$validParameters = [];
					$validParameters['TTL'] =
						new \Change\Presentation\Blocks\ParameterInformation('TTL', \Change\Documents\Property::TYPE_INTEGER,
							false, 60);

					foreach ($blockInformation->getParametersInformation() as $paramInfo)
					{
						$validParameters[$paramInfo->getName()] = $paramInfo;
					}

					if (!isset($parameters['TTL']) || !is_int($parameters['TTL']))
					{
						$parameters['TTL'] = intval($validParameters['TTL']->getDefaultValue());
					}

					$fullyQualifiedTemplateName = null;
					if (isset($parameters['fullyQualifiedTemplateName']))
					{
						$fullyQualifiedTemplateName = $parameters['fullyQualifiedTemplateName'];
					}

					$template = $blockInformation->getTemplateInformation($fullyQualifiedTemplateName);
					if ($template)
					{
						if ($fullyQualifiedTemplateName && $fullyQualifiedTemplateName != 'default:default')
						{
							$validParameters['fullyQualifiedTemplateName'] =
								new \Change\Presentation\Blocks\ParameterInformation('fullyQualifiedTemplateName',
									\Change\Documents\Property::TYPE_STRING, false, null);
						}

						foreach ($template->getParametersInformation() as $paramInfo)
						{
							$validParameters[$paramInfo->getName()] = $paramInfo;
						}
					}

					$normalizedParameters = [];
					foreach ($validParameters as $name => $validParameter)
					{
						$value = $validParameter->normalizeValue($parameters);
						if ($value !== null)
						{
							$normalizedParameters[$name] = $value;
						}
					}
					$block->setParameters($normalizedParameters);
				}
			}
		}
	}
}
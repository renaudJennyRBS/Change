<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Commands;

use Change\Http\Rest\Request;
use Change\Http\Rest\V1;
use Change\Http\Rest\V1\Resolver;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\V1\Commands\CommandsResolver
 */
class CommandsResolver implements \Change\Http\Rest\V1\NameSpaceDiscoverInterface
{
	const RESOLVER_NAME = 'commands';

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return [];
	}

	/**
	 * Set Event params: resourcesActionName, documentId, LCID
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		if ($nbParts == 0 && $method === Request::METHOD_GET)
		{
			$event->setAction(array($this, 'commandsList'));
			return;
		}
		elseif ($nbParts == 2)
		{
			$event->setParam('command', implode(':', $resourceParts));
			$event->setAction(array($this, 'executeCommand'));
			return;
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @return \Change\Events\EventManager
	 */
	protected function getCommandsEventManager(\Change\Http\Event $event)
	{
		$changeApplication = $event->getApplication();
		$eventManager = $changeApplication->getNewEventManager('Commands', 'Change/Events/Commands');
		return $eventManager;
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 * @param \Change\Application $application
	 * @return array
	 */
	protected function getCommandsConfiguration(\Change\Events\EventManager $eventManager, \Change\Application $application)
	{
		$cmdEvent = new \Change\Commands\Events\Event('config', $application, array());
		$results = $eventManager->trigger($cmdEvent);
		$commands = array();
		foreach ($results as $config)
		{
			if (!is_array($config))
			{
				continue;
			}

			foreach ($config as $commandName => $commandConfig)
			{
				$parameters = array();
				if (isset($commandConfig['options']) && is_array($commandConfig['options']))
				{
					foreach ($commandConfig['options'] as $optionName => $optionData)
					{
						if (array_key_exists('default', $optionData))
						{
							if (isset($optionData['default']))
							{
								$mode = 'Optional';
								$default = $optionData['default'];
							}
							else
							{
								$mode = 'Required';
								$default = null;
							}
						}
						else
						{
							$mode = 'Trigger';
							$default = null;
						}
						$description = isset($optionData['description']) ? $optionData['description'] : '';
						$parameters[$optionName] = array('mode' => $mode, 'description' => $description, 'default' => $default);
					}
				}
				if (isset($commandConfig['arguments']) && is_array($commandConfig['arguments']))
				{
					foreach ($commandConfig['arguments'] as $argumentName => $argumentData)
					{
						$description = isset($argumentData['description']) ? $argumentData['description'] : '';
						$mode = isset($argumentData['required']) && $argumentData['required']
							? 'Required' : 'Optional';
						$default = isset($argumentData['default']) ? $argumentData['default'] : null;
						$parameters[$argumentName] = array('mode' => $mode, 'description' => $description, 'default' => $default);
					}
				}
				$commands[$commandName] = $parameters;
			}
		}
		return $commands;
	}

	public function executeCommand(\Change\Http\Event $event)
	{
		$cmd = $event->getParam('command');
		$application = $event->getApplication();
		$eventManager = $this->getCommandsEventManager($event);
		$commands = $this->getCommandsConfiguration($eventManager, $application);
		if (isset($commands[$cmd]))
		{
			$args = array_merge($event->getRequest()->getQuery()->toArray(), $event->getRequest()->getPost()->toArray());
			$arguments = $eventManager->prepareArgs($args);
			$errorParameters = array();
			foreach ($commands[$cmd] as $name => $conf)
			{
				if (array_key_exists($name, $args))
				{
					if ($conf['mode'] === 'Trigger')
					{
						$arguments[$name] = true;
					}
					elseif (is_bool($conf['default']))
					{
						if ($arguments[$name] === 'true')
						{
							$arguments[$name] = true;
						}
						else
						{
							$arguments[$name] = false;
						}
					}
				}
				else
				{
					$value = ($conf['mode'] === 'Trigger') ? false : $conf['default'];
					$arguments[$name] = $value;
				}

				$value = $arguments[$name];
				if ($conf['mode'] === 'Required' && ($value === null || $value === ''))
				{
					$errorParameters[] = $name;
				}
			}

			if (count($errorParameters))
			{
				$message = 'Parameter(s) "' . implode('", "', $errorParameters) .'" required';
				$result = new V1\ErrorResult(999999, $message, Response::STATUS_CODE_409);
				$event->setResult($result);
				return;
			}
			$inputArgs = $arguments->getArrayCopy();
			$cmdEvent = new \Change\Commands\Events\Event($cmd, $application, $arguments);

			$response = new \Change\Commands\Events\RestCommandResponse();
			$cmdEvent->setCommandResponse($response);

			try
			{
				$eventManager->trigger($cmdEvent);

				$result = new V1\ArrayResult();
				$result->setArray(array('command'=> $cmd, 'inputArguments' => $inputArgs, 'result' => $cmdEvent->getCommandResponse()->toArray()));

				$result->setHttpStatusCode(Response::STATUS_CODE_200);
			}
			catch (\RuntimeException $e)
			{
				$result = new V1\ErrorResult($e->getCode(), $e->getMessage(), Response::STATUS_CODE_409);
			}
			catch (\Exception $e)
			{
				$result = new V1\ErrorResult($e->getCode(), $e->getMessage(), Response::STATUS_CODE_500);
			}

			$event->setResult($result);
		}

	}

	public function commandsList(\Change\Http\Event $event)
	{
		$application = $event->getApplication();
		$eventManager = $this->getCommandsEventManager($event);

		$commands = array();
		foreach ($this->getCommandsConfiguration($eventManager, $application) as $commandName => $parameters)
		{
				$link = new V1\Link($event->getUrlManager(),
					static::RESOLVER_NAME . '/' . str_replace(':', '/', $commandName));
				$commands[] = array('name' => $commandName, 'link' => $link->toArray(), 'parameters' => $parameters);
		}

		$result = new V1\ArrayResult();
		$result->setArray(array('commands' => $commands));
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}
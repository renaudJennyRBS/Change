<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\CommandsResolver
 */
class CommandsResolver
{
	const RESOLVER_NAME = 'commands';

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
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
		$eventManagerFactory = new \Change\Events\EventManagerFactory($changeApplication);
		foreach ($event->getServices() as $serviceName => $service)
		{
			$eventManagerFactory->addSharedService($serviceName, $service);
		}
		$eventManager = $eventManagerFactory->getNewEventManager('Commands');
		$classNames = $changeApplication->getConfiguration()->getEntry('Change/Events/Commands', array());
		$eventManagerFactory->registerListenerAggregateClassNames($eventManager, $classNames);
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
				if (array_key_exists($name, $arguments))
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
				$result = new \Change\Http\Rest\Result\ErrorResult(999999, $message, \Zend\Http\Response::STATUS_CODE_409);
				$event->setResult($result);
				return;
			}
			$inputArgs = $arguments->getArrayCopy();
			$cmdEvent = new \Change\Commands\Events\Event($cmd, $application, $arguments);
			$eventManager->trigger($cmdEvent);

			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setArray(array('command'=> $cmd, 'inputArguments' => $inputArgs, 'result' => $cmdEvent->getOutputMessages()));
			if ($cmdEvent->success())
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			}
			else
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
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
				$link = new \Change\Http\Rest\Result\Link($event->getUrlManager(),
					static::RESOLVER_NAME . '/' . str_replace(':', '/', $commandName));
				$commands[] = array('name' => $commandName, 'link' => $link->toArray(), 'parameters' => $parameters);
		}

		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setArray(array('commands' => $commands));
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}
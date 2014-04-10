<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\InitializeCommand
 */
class InitializeCommand
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$application = $event->getApplication();
		$cmdName = $event->getParam('cmdName');
		$validator = new \Zend\Validator\Regex('#^([a-z]+-{1})*[a-z]+$#');
		if (!$validator->isValid($cmdName))
		{
			$response->addErrorMessage('Command name should be a lowercase dash separated string');
			return;
		}

		$package = strtolower($event->getParam('package'));
		$vendor = null;
		$module = null;
		if ($package === 'change')
		{
			$namespace = 'Change\\Commands';
			$commandDir = $application->getWorkspace()->projectPath('Change', 'Commands');
		}
		else
		{
			$parts = explode('_', $package);
			if (count($parts) == 2)
			{
				$vendor = ucfirst(strtolower($parts[0]));
				$module = ucfirst(strtolower($parts[1]));
				$plugin = $event->getApplicationServices()->getPluginManager()->getModule($vendor, $module);
				if (!$plugin)
				{
					$response->addErrorMessage('Plugin not installed');
					return;
				}
			}
			else
			{
				$response->addErrorMessage('Package name should be of the form vendor_module or change');
				return;
			}

			$namespace = $vendor . '\\' . $module . '\\Commands';
			$commandDir = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'Commands';
		}
		$className = \Change\Stdlib\String::camelCase($cmdName);

		// Command class.
		$content = file_get_contents(__DIR__ . '/Assets/initialize-command/CommandTemplate.tpl');
		$content = str_replace(array('#namespace#', '#className#'), array($namespace, $className), $content);
		$filePath = $commandDir . DIRECTORY_SEPARATOR . $className . '.php' ;
		if (file_exists($filePath))
		{
			$response->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($commandDir . DIRECTORY_SEPARATOR . $className . '.php' , $content);

		$response->addInfoMessage('Command added at path ' . $filePath);

		// Listeners.
		$cmdFullName = $package . ':' . $cmdName;
		if (isset($plugin) && $plugin->getPackage())
		{
			$response->addWarningMessage('Your plugin is in a package. Look into its main plugin to find the listeners.');
			$response->addWarningMessage('Please add the following lines in this class to register your command:');
			$response->addWarningMessage(PHP_EOL . '		$callback = function ($event)');
			$response->addWarningMessage('		{');
			$response->addWarningMessage('			(new \\' . $namespace . '\\' . $className . '())->execute($event);');
			$response->addWarningMessage('		};');
			$response->addWarningMessage('		$events->attach(\'' . $cmdFullName . '\', $callback);' . PHP_EOL);
		}
		else
		{
			if ($package === 'change')
			{
				$filePath = $commandDir . DIRECTORY_SEPARATOR . 'Events' . DIRECTORY_SEPARATOR . 'ListenerAggregate.php' ;
			}
			else
			{
				$filePath = $commandDir . DIRECTORY_SEPARATOR . 'Listeners.php' ;
			}

			if (file_exists($filePath))
			{
				$response->addWarningMessage('Listeners Class File already exists at path ' . $filePath);
				$response->addWarningMessage('Please add the following lines in this class to register your command:');
				$response->addWarningMessage(PHP_EOL . '		$callback = function ($event)');
				$response->addWarningMessage('		{');
				$response->addWarningMessage('			(new \\' . $namespace . '\\' . $className . '())->execute($event);');
				$response->addWarningMessage('		};');
				$response->addWarningMessage('		$events->attach(\'' . $cmdFullName . '\', $callback);' . PHP_EOL);
			}
			else
			{
				$content = file_get_contents(__DIR__ . '/Assets/initialize-command/ListenersTemplate.tpl');
				$search = ['#namespace#', '#className#', '#package#', '#cmdName#'];
				$replace = [$namespace, $className, $package, $cmdName];
				$content = str_replace($search, $replace, $content);
				\Change\Stdlib\File::write($filePath , $content);

				$response->addInfoMessage('Command added at path ' . $filePath);
				$response->addWarningMessage('Please add the following line to your module setup to declare the listener:');
				$configKey = 'Change/Events/Commands/' . ucfirst($vendor) . '_' . ucfirst($module);
				$configValue = '\\' . ucfirst($vendor) .'\\' . ucfirst($module) . '\Command\Listeners';
				$response->addWarningMessage(PHP_EOL . '		$configuration->addPersistentEntry(\'' . $configKey . '\', \'' . $configValue . '\');' . PHP_EOL);
			}
		}

		// Command config.
		$filePath = $commandDir . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'config.json' ;
		if (isset($plugin) && $plugin->getPackage())
		{
			$response->addWarningMessage('Your plugin is in a package. Look into its main plugin to find the config.json for commands.');
			$response->addWarningMessage('You have to write on this file to add your new command arguments and options.');
		}
		elseif (file_exists($filePath))
		{
			$response->addWarningMessage('A config file already exists at path ' . $filePath);
			$response->addWarningMessage('You have to write on this file to add your new command arguments and options.');
		}
		else
		{
			$config = [
				$cmdFullName =>
					[
						'description' => 'your command description here',
						'dev' => false,
						'arguments' => [
							'firstArgument' => [
								'description' => 'first argument description here',
								'required' => true,
								'default' => null
							]
						],
						'options' => [
							'firstOption' => [
								'description' => 'first option',
								'shortcut' => 'o'
							]
						]
					]];

			\Change\Stdlib\File::write($filePath , json_encode($config, JSON_PRETTY_PRINT));
			$response->addInfoMessage('Command added at path ' . $filePath);
		}

		$response->addInfoMessage('Files successfully created for your command.');
	}
}
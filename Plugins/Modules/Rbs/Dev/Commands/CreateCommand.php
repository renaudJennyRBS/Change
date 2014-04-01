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
 * @name \Rbs\Dev\Commands\CreateCommand
 */
class CreateCommand
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$application = $event->getApplication();
		$cmdName = $event->getParam('cmdname');
		$validator = new \Zend\Validator\Regex('#^([a-z]+-{1})*[a-z]+$#');
		if (!$validator->isValid($cmdName))
		{
			$response->addErrorMessage('Command name should be a lowercase dash separated string');
			return;
		}
		$package = $event->getParam('package');
		$valid = ($package === 'change');
		if (!$valid)
		{
			$parts = explode('_', $package);
			if (count($parts) == 2)
			{
				$vendor = ucfirst(strtolower($parts[0]));
				$module = ucfirst(strtolower($parts[1]));
				$pathToTest = ($vendor == 'Project') ? $application->getWorkspace()->appPath('Modules', $module) :
					$application->getWorkspace()->projectPath('Plugins', 'Modules', $vendor, $module);
				$valid = is_dir($pathToTest);
			}
		}

		if (!$valid)
		{
			$response->addErrorMessage('Package name should be of the form vendor_module not installed');
			return;
		}

		$className = implode('', array_map('ucfirst', explode('-', $cmdName)));
		if (strtolower($package) === 'change')
		{
			$namespace = 'Change\\Commands';
			$commandDir = $application->getWorkspace()->projectPath('Change', 'Commands');
		}
		else
		{
			list($vendor, $module) = array_map(function($var){
				return ucfirst(strtolower($var));
			}, explode('_', $package));
			if ($vendor == 'Project')
			{
				$namespace = 'Project\\' . $module . '\\Commands';
				$commandDir = $application->getWorkspace()->appPath('Modules', $module , 'Commands');
			}
			else
			{
				$namespace = $vendor . '\\' . $module . '\\Commands';
				$commandDir = $application->getWorkspace()->projectPath('Plugins', 'Modules', $vendor, $module, 'Commands');
			}
		}

		$content = file_get_contents(__DIR__ . '/Assets/CommandTemplate.tpl');
		$content = str_replace(array('#namespace#', '#className#'), array($namespace, $className), $content);
		$filePath = $commandDir . DIRECTORY_SEPARATOR . $className . '.php' ;
		if (file_exists($filePath))
		{
			$response->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($commandDir . DIRECTORY_SEPARATOR . $className . '.php' , $content);

		$response->addInfoMessage('Command added at path ' . $filePath);

		//listeners
		$content = file_get_contents(__DIR__ . '/Assets/ListenersTemplate.tpl');
		$search = ['#namespace#', '#className#', '#package#', '#cmdName#'];
		$replace = [$namespace, $className, strtolower($package), $cmdName];
		$content = str_replace($search, $replace, $content);
		$filePath = $commandDir . DIRECTORY_SEPARATOR . 'Listeners.php' ;
		if (file_exists($filePath))
		{
			$response->addWarningMessage('Listeners Class File already exists at path ' . $filePath);
			$response->addWarningMessage('You have to write on this class to add the listener for your new command event');
		}
		else
		{
			\Change\Stdlib\File::write($filePath , $content);
			$response->addInfoMessage('Command added at path ' . $filePath);
		}

		$config = [
			strtolower($package) . ':' . $cmdName =>
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

		$filePath = $commandDir . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'config.json' ;
		if (file_exists($filePath))
		{
			$response->addWarningMessage('A config file already exists at path ' . $filePath);
			$response->addWarningMessage('You have to write on this file to add your new command arguments and options');
		}
		else
		{
			\Change\Stdlib\File::write($filePath , json_encode($config, JSON_PRETTY_PRINT));
			$response->addInfoMessage('Command added at path ' . $filePath);
		}

		$response->addInfoMessage('Files successfully created for your command. Don\'t forget to declare the listener in you module setup');
	}
}
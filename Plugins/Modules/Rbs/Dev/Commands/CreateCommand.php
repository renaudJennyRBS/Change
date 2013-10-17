<?php
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
		$application = $event->getApplication();
		$cmdName = $event->getParam('cmdname');
		$validator = new \Zend\Validator\Regex('#^([a-z]+-{1})*[a-z]+$#');
		if (!$validator->isValid($cmdName))
		{
			$event->addErrorMessage('Command name should be a lowercase dash separated string');
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
			$event->addErrorMessage('Package name should be of the form vendor_module not installed');
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
			$event->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($commandDir . DIRECTORY_SEPARATOR . $className . '.php' , $content);

		$event->addInfoMessage('Command added at path ' . $filePath);
	}
}
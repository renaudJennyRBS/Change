<?php
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\initialize-block
 */
class InitializeBlock
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$application = $event->getApplication();
		$blockName = $event->getParam('blockName');
		$validator = new \Zend\Validator\Regex('#^([A-Z][a-zA-Z0-9]+)$#');
		if (!$validator->isValid($blockName))
		{
			$response->addErrorMessage('Block name should be camel-cased');
			return;
		}

		$package = $event->getParam('package');
		$parts = explode('_', $package);
		if (count($parts) != 2)
		{
			$response->addErrorMessage('Package name should be of the form vendor_module');
			return;
		}

		$vendor = ucfirst(strtolower($parts[0]));
		$module = ucfirst(strtolower($parts[1]));
		$blockFullName = $vendor . '_' . $module . '_' . $blockName;
		$plugin = $event->getApplicationServices()->getPluginManager()->getModule($vendor, $module);
		if (!$plugin)
		{
			$response->addErrorMessage('Plugin not installed');
			return;
		}

		$className = ucfirst($blockName);
		$namespace = $vendor . '\\' . $module . '\\Blocks';
		$blockDir = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'Blocks';
		$templateName = \Change\Stdlib\String::snakeCase($className, '-') . '.twig';
		$localeName = \Change\Stdlib\String::snakeCase($className);

		// Main block class.
		$content = file_get_contents(__DIR__ . '/Assets/initialize-block/BlockTemplate.tpl');
		$replacementNames = array('#namespace#', '#className#', '#templateName#');
		$replacementValues = array($namespace, $className, $templateName);
		$content = str_replace($replacementNames, $replacementValues, $content);
		$filePath = $blockDir . DIRECTORY_SEPARATOR . $className . '.php' ;
		if (file_exists($filePath))
		{
			$response->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($filePath, $content);
		$response->addInfoMessage('Main block class added at path ' . $filePath);

		// Block information class.
		$content = file_get_contents(__DIR__ . '/Assets/initialize-block/BlockInformationTemplate.tpl');
		$replacementNames = array('#namespace#', '#className#', '#vendor#', '#lowerVendor#', '#module#', '#lowerModule#', '#localeName#');
		$replacementValues = array($namespace, $className, $vendor, strtolower($vendor), $module, strtolower($module), $localeName);
		$content = str_replace($replacementNames, $replacementValues, $content);
		$filePath = $blockDir . DIRECTORY_SEPARATOR . $className . 'Information.php';
		if (file_exists($filePath))
		{
			$response->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($filePath, $content);
		$response->addInfoMessage('Block information class added at path ' . $filePath);

		// Template file.
		$content = file_get_contents(__DIR__ . '/Assets/initialize-block/BlockTemplateTemplate.tpl');
		$replacementNames = array('#lowerVendor#', '#lowerModule#', '#localeName#');
		$replacementValues = array(strtolower($vendor),  strtolower($module), $localeName);
		$content = str_replace($replacementNames, $replacementValues, $content);
		if ($vendor == 'Project')
		{
			$assetsLicensePath = $application->getWorkspace()->projectModulesPath($vendor, $module, 'Assets', 'LICENSE.txt');
			$filePath = $application->getWorkspace()->projectModulesPath($vendor, $module, 'Assets', 'Twig', 'Blocks', $templateName);
		}
		else
		{
			$assetsLicensePath = $application->getWorkspace()->pluginsModulesPath($vendor, $module, 'Assets', 'LICENSE.txt');
			$filePath = $application->getWorkspace()->pluginsModulesPath($vendor, $module, 'Assets', 'Twig', 'Blocks', $templateName);
		}
		if (!file_exists($assetsLicensePath))
		{
			\Change\Stdlib\File::write($assetsLicensePath, file_get_contents(__DIR__ . '/Assets/LICENSE.txt'));
		}
		if (file_exists($filePath))
		{
			$response->addErrorMessage('File already exists at path ' . $filePath);
			return;
		}
		\Change\Stdlib\File::write($filePath, $content);
		$response->addInfoMessage('Template file added at path ' . $filePath);

		// Listeners.
		$filePath = $blockDir . DIRECTORY_SEPARATOR . 'Listeners.php' ;
		if ($plugin->getPackage())
		{
			$response->addWarningMessage('Your plugin is in a package. Look into its main plugin for the listener class for BlockManager.');
			$response->addWarningMessage('Please add the following line the attach() method of this class to register your block:');
			$response->addWarningMessage(PHP_EOL . '		new RegisterByBlockName(\'' . $blockFullName . '\', true, $events);' . PHP_EOL);
		}
		elseif (file_exists($filePath))
		{
			$response->addWarningMessage('Listeners Class File for BlockManager already exists at path ' . $filePath);
			$response->addWarningMessage('Please add the following line the attach() method of this class to register your block:');
			$response->addWarningMessage(PHP_EOL . '		new RegisterByBlockName(\'' . $blockFullName . '\', true, $events);' . PHP_EOL);
		}
		else
		{
			$content = file_get_contents(__DIR__ . '/Assets/initialize-block/ListenersTemplate.tpl');
			$search = ['#namespace#', '#className#', '#package#', '#blockFullName#'];
			$replace = [$namespace, $className, $package, $blockFullName];
			$content = str_replace($search, $replace, $content);
			\Change\Stdlib\File::write($filePath , $content);

			$response->addInfoMessage('Command added at path ' . $filePath);
			$response->addWarningMessage('Please add the following line to your module setup to declare the listener:');
			$configKey = 'Change/Events/BlockManager/' . ucfirst($vendor) . '_' . ucfirst($module);
			$configValue = '\\' . ucfirst($vendor) .'\\' . ucfirst($module) . '\Blocks\Listeners';
			$response->addWarningMessage(PHP_EOL . '		$configuration->addPersistentEntry(\'' . $configKey . '\', \'' . $configValue . '\');' . PHP_EOL);
		}

		$response->addInfoMessage('Files successfully created for your block.');
	}
}
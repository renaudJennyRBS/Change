<?php

namespace Change\Application\Console;

use Symfony\Component\Finder\Glob;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class ConsoleApplication extends \Symfony\Component\Console\Application
{
	/**
	 * @var array
	 */
	protected $cmdAliases;

	/**
	 * @var \Change\Application
	 */
	protected $changeApplication;
	
	/**
	 * @return \Change\Application
	 */
	public function getChangeApplication()
	{
		return $this->changeApplication;
	}

	/**
	 * @param \Change\Application $changeApplication
	 */
	public function setChangeApplication(\Change\Application $changeApplication)
	{
		$this->changeApplication = $changeApplication;
	}

	/**
	 * Registers all the commands
	 */
	public function registerCommands()
	{
		// loads the potential shortcuts file
		if (file_exists(PROJECT_HOME . '/aliases.json'))
		{
			$this->cmdAliases = json_decode(file_get_contents(PROJECT_HOME . '/aliases.json'), true);
		}
		
		$changeCommandDir = new \SplFileInfo(PROJECT_HOME . '/Change/Commands');
		$this->registerCommandsInDir($changeCommandDir);
		
		// Register project commands
		$finder = new Finder();
		$dirs = $finder->depth("== 1")->directories()->in(PROJECT_HOME . '/App/Modules/')->name('Commands');
		foreach ($dirs as $dir)
		{
			$this->registerCommandsInDir($dir, 'project');
		}
		
		$finder = new Finder();
		$vendorModuleDirs = $finder->directories()->in(PROJECT_HOME . '/Plugins/Modules/')->depth('==1');
		foreach ($vendorModuleDirs as $vendorModuleDir)
		{
			$commandDir = new \SplFileInfo($vendorModuleDir->getPathname() . DIRECTORY_SEPARATOR . 'Commands');
			if ($commandDir->isDir())
			{
				$pathComponents = explode(DIRECTORY_SEPARATOR, $commandDir->getPath());
				$moduleName = array_pop($pathComponents);
				$vendorName = array_pop($pathComponents);
				$this->registerCommandsInDir($commandDir, strtolower($vendorName) . '-' . strtolower($moduleName), '\\' . $vendorName . '\\' . $moduleName . '\\' . 'Commands');
			}
		}
	}
	
	/**
	 * @param \SplFileInfo $dir
	 * @param string $group
	 */
	protected function registerCommandsInDir(\SplFileInfo $dir, $group = null, $namespace = null)
	{
		$cmdFinder = new Finder();
		foreach ($cmdFinder->files()->depth('== 0')->in($dir->getPathname())->name('*.php') as $file)
		{
			/* @var $file SplFileInfo*/
			$shortClassName = str_replace('.php', '', $file->getFilename());
			if ($namespace === null)
			{
				$namespace = str_replace(DIRECTORY_SEPARATOR, '\\', substr($file->getPath(), strlen(PROJECT_HOME)));
			}
			$commandClassName = $namespace .  '\\' . $shortClassName;
			if (class_exists($commandClassName))
			{
				$commandName = strtolower($shortClassName[0] . preg_replace('/([A-Z])/', '-${0}', substr($shortClassName, 1)));
				if ($group)
				{
					$commandName =  $group . ':' . $commandName;  
				}
				/* @var $command \Change\Application\Console\AbstractCommand */
				$command = new $commandClassName($commandName);
				if (isset($this->cmdAliases[$commandName]))
				{
					$currentAliases = $command->getAliases();
					$currentAliases[] = $this->cmdAliases[$commandName];
					$command->setAliases($currentAliases);
				}
				$command->setChangeApplication($this->getChangeApplication());
				$this->add($command);
			}
		}
	}
	
	/**
	 * @return \Symfony\Component\Console\Input\InputDefinition
	 */
	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(new InputOption('--dev',           '-d', InputOption::VALUE_NONE, 'Force developer mode'));
		return $definition;
	}
}
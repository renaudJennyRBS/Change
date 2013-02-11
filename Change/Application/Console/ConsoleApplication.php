<?php
namespace Change\Application\Console;

use Zend\Json\Json;
use Change\Stdlib\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

/**
 * @name \Change\Application\Console\ConsoleApplication
 */
class ConsoleApplication extends \Symfony\Component\Console\Application
{
	/**
	 * @var array
	 */
	protected $configuration;
	
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
	 * @return array
	 */
	public function getConfiguration()
	{
		if (!$this->configuration)
		{
			$globalConfig = array();
			$projectConfig = array();
			if (isset($_SERVER['HOME']) && file_exists($_SERVER['HOME'] . '/.console.json'))
			{
				$globalConfig = Json::decode(file_get_contents($_SERVER['HOME'] . '/.console.json'), Json::TYPE_ARRAY);
			}
			$projectConfigFile = $this->getChangeApplication()->getWorkspace()->appPath('Config', 'console.json');
			if (file_exists($projectConfigFile))
			{
				$projectConfig = Json::decode(file_get_contents($projectConfigFile), Json::TYPE_ARRAY);
			}
			$this->configuration = array_merge_recursive($globalConfig, $projectConfig);
		}
		return $this->configuration;
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
		$changeCommandDir = new \SplFileInfo(PROJECT_HOME . '/Change/Commands');
		$this->registerCommandsInDir($changeCommandDir, 'change');
		
		// Register project commands
		$finder = new Finder();
		$dirs = $finder->depth("== 1")->directories()->in(PROJECT_HOME . '/App/Modules/')->name('Commands');
		foreach ($dirs as $dir)
		{
			$pathComponents = explode(DIRECTORY_SEPARATOR, $dir->getPath());
			$moduleName = array_pop($pathComponents);
			$this->registerCommandsInDir($dir, 'project', '\\Project\\' . ucfirst(strtolower($moduleName)) . '\\Commands');
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
				$this->registerCommandsInDir($commandDir, strtolower($vendorName) . '-' . strtolower($moduleName), '\\' . $vendorName . '\\' . $moduleName . '\\Commands');
			}
		}
		
		$this->registerCommandGroups();
	}
	
	/**
	 * @throws \RuntimeException
	 */
	protected function registerCommandGroups()
	{
		$config = $this->getConfiguration();
		$groups = isset($config['groups']) ? $config['groups'] : array();
		foreach ($groups as $name => $commandNames)
		{
			$command = new ChangeCommand($name);
			$application = $this;
			$command->addOption('--isolation', null, InputOption::VALUE_NONE, 'Run commands in separate processes');
			$command->addOption('--ignore-errors', null, InputOption::VALUE_NONE, 'Ignore subcommand error');
			$command->setCode(function(InputInterface $input, OutputInterface $output) use ($commandNames, $application) {
				
				$style = new OutputFormatterStyle('yellow', null, array('bold'));
				$output->getFormatter()->setStyle('strong', $style);
				foreach ($commandNames as $commandName)
				{
					$output->writeln("");
					$output->writeln("<strong>Executing command $commandName</strong>");
					$output->writeln("");
					if ($input->getOption('isolation'))
					{
						$process = new Process($this->getConfiguration()->getEntry('Change/Application/php-cli-path') . ' ' . $_SERVER['argv'][0] . " $commandName " . ($input->getOption('dev') ? '--dev' : ''));
						$process->run(function($type, $buffer) use ($output) {
							$output->write($buffer, false, OutputInterface::OUTPUT_RAW);
						});
					}
					else
					{
						$command = $application->find($commandName);
						$subCommandInput = new ArrayInput(array('command' => $commandName, '--dev' => $input->getOption('dev')));
						$returnCode = $command->run($subCommandInput, $output);
						if ($returnCode && !$this->getOption('ignore-errors'))
						{
							throw new \RuntimeException('Command ' . $commandName . ' failed', $returnCode);
						}
					}
				}
			});
			$command->setChangeApplication($this->getChangeApplication());
			$this->add($command);
		}
	}
	
	/**
	 * @param \SplFileInfo $dir
	 * @param string $group
	 */
	protected function registerCommandsInDir(\SplFileInfo $dir, $group = null, $namespace = null)
	{
		$config = $this->getConfiguration();
		$aliases = isset($config['aliases']) ? $config['aliases'] : array();
		
		$cmdFinder = new Finder();
		foreach ($cmdFinder->files()->depth('== 0')->in($dir->getPathname())->name('*.php') as $file)
		{
			/* @var $file SplFileInfo*/
			$shortClassName = str_replace('.php', '', $file->getFilename());
			if ($namespace === null)
			{
				$namespace = str_replace(DIRECTORY_SEPARATOR, '\\', substr($file->getPath(), strlen(PROJECT_HOME)));
			}
			$commandClassName = $namespace . '\\' . $shortClassName;
			if (class_exists($commandClassName))
			{
				$commandName = strtolower($shortClassName[0] . preg_replace('/([A-Z])/', '-${0}', substr($shortClassName, 1)));
				if ($group)
				{
					$commandName = $group . ':' . $commandName;
				}
				/* @var $command \Change\Application\Console\ChangeCommand */
				$command = new $commandClassName($commandName);
				if (isset($aliases[$commandName]))
				{
					$currentAliases = $command->getAliases();
					if (is_array($aliases[$commandName]))
					{
						$currentAliases = array_merge($currentAliases, $aliases[$commandName]);
					}
					elseif (is_string($aliases[$commandName]))
					{
						$currentAliases[] = $aliases[$commandName];
					}
					
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
		$definition->addOption(new InputOption('--dev', '-d', InputOption::VALUE_NONE, 'Force developer mode'));
		return $definition;
	}
}
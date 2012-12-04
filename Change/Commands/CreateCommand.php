<?php
namespace Change\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\CreateCommand
 */
class CreateCommand extends \Change\Application\Console\ChangeCommand
{
	/**
	 * @return boolean
	 */
	public function isDevCommand()
	{
		return true;
	}

	/**
	 */
	protected function configure()
	{
		$this->setDescription('Create an empty console command')
		->addArgument('package', InputArgument::REQUIRED, 'name of the package (vendor/module or change)')
		->addArgument('cmdname', InputArgument::REQUIRED, 'name of the command (e.g. my-cmd)');
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \RuntimeException
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$cmdName = $input->getArgument('cmdname');
		$validator = new \Zend\Validator\Regex('#^([a-z]+-{1})*[a-z]+$#');
		if (!$validator->isValid($cmdName))
		{
			throw new \InvalidArgumentException('Command name should be a lowercase dash separated string');
		}
		$package = $input->getArgument('package');
		$valid = ($package === 'change');
		if (!$valid)
		{
			$parts = explode('/', $package);
			if (count($parts) == 2)
			{
				$vendor = ucfirst(strtolower($parts[0]));
				$module = ucfirst(strtolower($parts[1]));
				$pathToTest = ($vendor == 'Project') ? $this->getChangeApplication()->getWorkspace()->appPath('Modules', $module) : $this->getChangeApplication()->getWorkspace()->projectPath('Plugins', 'Modules', $vendor, $module);
				echo $pathToTest, PHP_EOL;
				$valid = is_dir($pathToTest);
			}
		}

		if (!$valid)
		{
			throw new \InvalidArgumentException('Package name should be of the form vendor/module or change or package not installed');
		}
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$className = implode('', array_map('ucfirst', explode('-', $input->getArgument('cmdname'))));
		$package = $input->getArgument('package');
		if (strtolower($package) === 'change')
		{
			$namespace = 'Change\\Commands';
			$commandDir = $this->getChangeApplication()->getWorkspace()->projectPath('Change', 'Commands');
		}
		else
		{
			list($vendor, $module) = array_map(function($var){
				return ucfirst(strtolower($var));
			}, explode('/', $package));
			if ($vendor == 'Project')
			{
				$namespace = 'Project\\' . $module . '\\Commands';
				$commandDir = $this->getChangeApplication()->getWorkspace()->appPath('Modules', $module , 'Commands');
			}
			else
			{
				$namespace = $vendor . '\\' . $module . '\\Commands';
				$commandDir = $this->getChangeApplication()->getWorkspace()->projectPath('Plugins', 'Modules', $vendor, $module, 'Commands');
			}
		}
		$content = file_get_contents(__DIR__ . '/Assets/CommandTemplate.tpl');
		$content = str_replace(array('#namespace#', '#className#'), array($namespace, $className), $content);
		$filePath = $commandDir . DIRECTORY_SEPARATOR . $className . '.php' ;
		if (file_exists($filePath))
		{
			throw new \RuntimeException('File already exists at path ' . $filePath);
		}
		\Change\Stdlib\File::write($commandDir . DIRECTORY_SEPARATOR . $className . '.php' , $content);
	}
}
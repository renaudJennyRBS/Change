<?php
namespace Change\Http;

/**
 * @name \Change\Http\InitHttpFiles
 */
class InitHttpFiles
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @param \Change\Application $application
	 */
	function __construct(\Change\Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @param string $webBaseDirectory
	 * @param string $webBaseURLPath
	 */
	public function initializeControllers($webBaseDirectory, $webBaseURLPath)
	{
		$editConfig = new \Change\Configuration\EditableConfiguration(array());
		$editConfig->import($this->application->getConfiguration());
		$workspace = $this->application->getWorkspace();

		$srcPath = $workspace->changePath('Http', 'Assets', 'rest.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export($this->application->getWorkspace()->projectPath(), true), $content);


		$rootPath = $workspace->composeAbsolutePath($webBaseDirectory);
		\Change\Stdlib\File::write($rootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);

		$editConfig->addPersistentEntry('Change/Install/webBaseDirectory', $webBaseDirectory, \Change\Configuration\Configuration::PROJECT);
		$editConfig->addPersistentEntry('Change/Install/webBaseURLPath', $webBaseURLPath, \Change\Configuration\Configuration::PROJECT);

		$editConfig->save();

		$assetsRootPath = $rootPath . DIRECTORY_SEPARATOR . 'Assets';
		\Change\Stdlib\File::mkdir($assetsRootPath);
	}
}
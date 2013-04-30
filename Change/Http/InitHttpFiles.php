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
	 * @param $documentRootPath
	 */
	public function initializeControllers($documentRootPath)
	{
		$srcPath = $this->application->getWorkspace()->changePath('Http', 'Assets', 'index.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export($this->application->getWorkspace()->projectPath(), true), $content);
		\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);

		$srcPath = $this->application->getWorkspace()->changePath('Http', 'Assets', 'rest.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export(PROJECT_HOME, true), $content);
		\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);

		$srcPath = $this->application->getWorkspace()->changePath('Http', 'Assets', 'admin.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export(PROJECT_HOME, true), $content);
		\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);
	}
}
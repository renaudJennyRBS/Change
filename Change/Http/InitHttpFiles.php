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
	 * @param string $documentRootPath
	 * @param string $resourcePath
	 */
	public function initializeControllers($documentRootPath, $resourcePath)
	{
		$editConfig = new \Change\Configuration\EditableConfiguration(array());
		$editConfig->import($this->application->getConfiguration());

		$srcPath = $this->application->getWorkspace()->changePath('Http', 'Assets', 'rest.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export($this->application->getWorkspace()->projectPath(), true), $content);
		\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);

		$editConfig->addPersistentEntry('Change/Install/documentRootPath', $documentRootPath, \Change\Configuration\EditableConfiguration::PROJECT);
		$editConfig->addPersistentEntry('Change/Install/resourceBaseUrl', $resourcePath, \Change\Configuration\EditableConfiguration::PROJECT);
		$editConfig->save();

		if (strpos($resourcePath, '/') === 0)
		{
			$webResourcePath = $this->application->getWorkspace()->composePath($documentRootPath, $resourcePath);
			\Change\Stdlib\File::mkdir($webResourcePath);
		}
	}
}
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
		$editConfig = new \Change\Configuration\EditableConfiguration(array());
		$editConfig->import($this->application->getConfiguration());

		$srcPath = $this->application->getWorkspace()->changePath('Http', 'Assets', 'rest.php');
		$content = \Change\Stdlib\File::read($srcPath);
		$content = str_replace('__DIR__', var_export(PROJECT_HOME, true), $content);
		\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);

		$editConfig->addPersistentEntry('Change/Install/documentRootPath', $documentRootPath,
			\Change\Configuration\EditableConfiguration::INSTANCE);

		$editConfig->save();
	}
}
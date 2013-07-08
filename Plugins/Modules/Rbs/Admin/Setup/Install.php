<?php
namespace Rbs\Admin\Setup;

/**
 * @name \Change\Generic\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

		$projectPath = $application->getWorkspace()->projectPath();
		$documentRootPath = $config->getEntry('Change/Install/documentRootPath', $projectPath);

		if (is_dir($documentRootPath))
		{
			$srcPath = __DIR__ . '/Assets/admin.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($projectPath, true), $content);
			\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: ' . $documentRootPath .
			'. Check "Change/Install/documentRootPath" configuration entry.', 999999);
		}

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Admin', '\\Rbs\\Admin\\Register');

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Admin', '\\Rbs\\Admin\\SharedListenerAggregate');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $documentServices, $presentationServices)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
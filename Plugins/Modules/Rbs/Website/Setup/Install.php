<?php
namespace Rbs\Website\Setup;

/**
 * @name \Rbs\Website\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $config
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $config)
	{
		$projectPath = $application->getWorkspace()->projectPath();
		$documentRootPath = $config->getEntry('Change/Install/documentRootPath', $projectPath);

		if (is_dir($documentRootPath))
		{
			$srcPath = __DIR__ . '/Assets/index.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($projectPath, true), $content);
			\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: '. $documentRootPath .
			'. Check "Change/Install/documentRootPath" configuration entry.', 999999);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$presentationServices->getThemeManager()->installPluginTemplates($plugin);
		$rootNode = $documentServices->getTreeManager()->getRootNode('Rbs_Website');
		if (!$rootNode)
		{
			$transactionManager = $applicationServices->getTransactionManager();

			try
			{
				$transactionManager->begin();

				/* @var $folder \Rbs\Generic\Documents\Folder */
				$folder = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
				$folder->setLabel('Rbs_Website');
				$folder->create();
				$documentServices->getTreeManager()->insertRootNode($folder, 'Rbs_Website');
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
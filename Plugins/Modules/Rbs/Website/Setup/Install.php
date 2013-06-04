<?php
namespace Change\Website\Setup;

/**
 * @name \Change\Website\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

		$config->addPersistentEntry('Change/Admin/Listeners/Change_Website',
				'\\Change\\Website\\Admin\\Register');
		$config->addPersistentEntry('Change/Presentation/Blocks/Change_Website',
			'\\Change\\Website\\Blocks\\SharedListenerAggregate');

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Change_Website',
				'\\Change\\Website\\Events\\SharedListenerAggregate');

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
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $documentServices, $presentationServices)
	{
		$rootNode = $documentServices->getTreeManager()->getRootNode('Change_Website');
		if (!$rootNode)
		{
			/* @var $folder \Change\Generic\Documents\Folder */
			$folder = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Generic_Folder');
			$folder->setLabel('Change_Website');
			$folder->create();
			$rootNode = $documentServices->getTreeManager()->insertRootNode($folder, 'Change_Website');

			/* @var $website \Change\Website\Documents\Website */
			$website = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Website_Website');
			$website->setLabel('Site par défaut');
			$website->setHostName('temporary.fr');
			$website->setScriptName('/index.php');
			$website->create();
			$documentServices->getTreeManager()->insertNode($rootNode, $website);
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
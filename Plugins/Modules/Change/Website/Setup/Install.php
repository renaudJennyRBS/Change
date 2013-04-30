<?php
namespace Change\Website\Setup;

/**
 * Class Install
 * @package Change\Website\Setup
 * @name \Change\Website\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{
		$application->getConfiguration()->addPersistentEntry('Change/Admin/Listeners/Change_Website', '\\Change\\Website\\Admin\\Register');

		$application->getConfiguration()->addPersistentEntry('Change/Presentation/Blocks/Change_Website', '\\Change\\Website\\Blocks\\SharedListenerAggregate');
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function executeServices($applicationServices, $documentServices)
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
}
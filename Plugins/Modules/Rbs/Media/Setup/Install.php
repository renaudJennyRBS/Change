<?php
namespace Rbs\Media\Setup;

/**
 * @name \Rbs\Media\Setup\Install
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
		$images = array('class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => $application->getWorkspace()->appPath('Storage', 'images'),
			'useDBStat' => true, 'baseURL' => false
		);
		$config->addPersistentEntry('Change/Storage/images', $images, \Change\Configuration\Configuration::INSTANCE);

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Media', '\\Rbs\\Media\\Admin\\Register');

	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
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
		$this->createDefaultTags($applicationServices, $documentServices);
	}


	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @throws
	 */
	private function createDefaultTags($applicationServices, $documentServices)
	{
		$tagModel = $documentServices->getModelManager()->getModelByName('Rbs_Tag_Tag');
		$documentManager = $documentServices->getDocumentManager();

		$tags = array(
			'grande'  => 'grey',
			'moyenne' => 'grey'
		);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			foreach ($tags as $label => $color)
			{
				$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Tag_Tag');
				$tag = $query->andPredicates($query->eq('label', $label))->getFirstDocument();
				if (!$tag)
				{
					/* @var $tag \Rbs\Tag\Documents\Tag */
					$tag = $documentManager->getNewDocumentInstanceByModel($tagModel);
					$tag->setLabel($label);
					$tag->setColor($color);
					$tag->create();
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
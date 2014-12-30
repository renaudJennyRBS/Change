<?php
namespace Rbs\Storelocator\Setup;

/**
 * @name \Rbs\Storelocator\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function attach($events, $plugin)
//	{
//		parent::attach($events, $plugin);
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function initialize($plugin)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Storelocator',
			'\Rbs\Storelocator\Events\SharedListeners');

		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Storelocator',
			'\Rbs\Storelocator\Events\BlockManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/PageManager/Rbs_Storelocator',
			'\Rbs\Storelocator\Events\PageManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/Http/Ajax/Rbs_Storelocator',
			'\Rbs\Storelocator\Events\Http\Ajax\Listeners');

		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Storelocator',
			'\Rbs\Storelocator\Collection\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
//	public function executeDbSchema($plugin, $schemaManager)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices)
	{
		parent::executeServices($plugin, $applicationServices);

		$applicationServices->getDocumentCodeManager();

		$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager());
		$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

		$json = json_decode(file_get_contents(__DIR__ . '/Assets/AddressFields.json'), true);
		try
		{
			$applicationServices->getTransactionManager()->begin();
			$import->fromArray($json);
			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function finalize($plugin)
//	{
//	}
}

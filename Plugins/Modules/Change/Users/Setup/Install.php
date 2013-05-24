<?php
namespace Change\Users\Setup;

use Change\Plugins\PluginManager;

/**
 * Class Install
 * @package Change\Users\Setup
 * @name \Change\Users\Setup\Install
 */
class Install implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * @return string
	 */
	protected function getVendor()
	{
		return 'change';
	}

	/**
	 * @return string
	 */
	protected function getName()
	{
		return 'users';
	}

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param \Zend\EventManager\EventManagerInterface $events
	 */
	public function attach(\Zend\EventManager\EventManagerInterface $events)
	{
		$vendor = $this->getVendor();
		$name = $this->getName();

		$callBack = function (\Zend\EventManager\Event $event) use ($vendor, $name)
		{
			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			return $pluginManager->getModule($vendor, $name)->setPackage('core')->setConfigurationEntry('locked', true);
		};

		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_PACKAGE, $vendor, 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_MODULE, $vendor, $name)
		);
		$events->attach($eventNames, $callBack, 5);

		$callBack = function (\Zend\EventManager\Event $event) use ($vendor, $name)
		{
			/* @var $application \Change\Application */
			$application = $event->getParam('application');
			$this->executeApplication($application);

			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			$pluginManager->getModule($vendor, $name)
				->setConfigurationEntry(PluginManager::EVENT_SETUP_APPLICATION, 'Ok');
		};
		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_APPLICATION, PluginManager::EVENT_TYPE_PACKAGE, $vendor, 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_APPLICATION, PluginManager::EVENT_TYPE_MODULE, $vendor, $name)
		);
		$events->attach($eventNames, $callBack, 5);

		$callBack = function (\Zend\EventManager\Event $event) use ($vendor, $name)
		{

			/* @var $documentServices \Change\Documents\DocumentServices */
			$documentServices = $event->getParam('documentServices');
			$this->executeServices($documentServices);

			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			$pluginManager->getModule($vendor, $name)
				->setConfigurationEntry(PluginManager::EVENT_SETUP_SERVICES, 'Ok');
		};

		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_SERVICES, PluginManager::EVENT_TYPE_PACKAGE, $vendor, 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_SERVICES, PluginManager::EVENT_TYPE_MODULE, $vendor, $name)
		);
		$events->attach($eventNames, $callBack, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param \Zend\EventManager\EventManagerInterface $events
	 */
	public function detach(\Zend\EventManager\EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{
		$application->getConfiguration()
			->addPersistentEntry('Change/Presentation/Blocks/Change_Users', '\\Change\\Users\\Blocks\\SharedListenerAggregate');
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function executeServices($documentServices)
	{
		$groupModel = $documentServices->getModelManager()->getModelByName('Change_Users_Group');
		$query = new \Change\Documents\Query\Builder($documentServices, $groupModel);
		$group = $query->andPredicates($query->eq('realm', 'rest'))->getFirstDocument();
		if (!$group)
		{
			/* @var $group \Change\Users\Documents\Group */
			$group = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
			$group->setLabel('Backoffice');
			$group->setRealm('rest');
			$group->create();

			/* @var $group2 \Change\Users\Documents\Group */
			$group2 = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
			$group2->setLabel('Site Web');
			$group2->setRealm('web');
			$group2->create();

			/* @var $user \Change\Users\Documents\User */
			$user = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Users_User');
			$user->setLabel('Administrateur');
			$user->setEmail('admin@temporary.fr');
			$user->setLogin('admin');
			$user->setPassword('admin');
			$user->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
			$user->addGroups($group);
			$user->addGroups($group2);
			$user->create();
		}
	}
}
<?php
namespace Theme\Change\Demo\Setup;

use Change\Plugins\PluginManager;

/**
 * @name \Theme\Change\Demo\Setup
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
		return 'demo';
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
			return $pluginManager->getTheme($vendor, $name)->setPackage('core');
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
			/* @var $documentServices \Change\Documents\DocumentServices */
			$documentServices = $event->getParam('documentServices');
			$this->executeServices($documentServices);

			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			$pluginManager->getTheme($vendor, $name)->setConfigurationEntry(PluginManager::EVENT_SETUP_SERVICES, 'Ok');
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
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	protected function executeServices($documentServices)
	{
		$themeModel = $documentServices->getModelManager()->getModelByName('Change_Theme_Theme');
		$query = new \Change\Documents\Query\Builder($documentServices, $themeModel);
		$query->andPredicates($query->eq('name', 'Change_Demo'));
		$theme = $query->getFirstDocument();
		if ($theme instanceof \Change\Theme\Documents\Theme)
		{
			return;
		}
		/* @var $theme \Change\Theme\Documents\Theme */
		$theme = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
		$theme->setLabel('Demo');
		$theme->setName('Change_Demo');
		$theme->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$theme->save();

		$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Change_Theme_PageTemplate');

		/* @var $pageTemplate \Change\Theme\Documents\PageTemplate */
		$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
		$pageTemplate->setTheme($theme);

		$pageTemplate->setLabel('Sample');
		$html = file_get_contents(__DIR__ . '/Assets/Sample.twig');
		$pageTemplate->setHtml($html);
		$json = file_get_contents(__DIR__ . '/Assets/Sample.json');
		$pageTemplate->setEditableContent($json);
		$pageTemplate->setHtmlForBackoffice('<div data-editable-zone-id="zoneEditable1"></div>');
		$pageTemplate->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$pageTemplate->save();
	}
}
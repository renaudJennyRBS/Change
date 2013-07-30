<?php
namespace Rbs\Generic\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Generic\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addSortDirections(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'asc' => new I18nString($i18n, 'm.rbs.generic.ascending', array('ucf')),
				'desc' => new I18nString($i18n, 'm.rbs.generic.descending', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_SortDirections', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addPermissionRoles(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'*' => new I18nString($i18n, 'm.rbs.generic.any-role', array('ucf')),
				'Consumer' => new I18nString($i18n, 'm.rbs.generic.role-consumer', array('ucf')),
				'Creator' => new I18nString($i18n, 'm.rbs.generic.role-creator', array('ucf')),
				'Editor' => new I18nString($i18n, 'm.rbs.generic.role-editor', array('ucf')),
				'Publisher' => new I18nString($i18n, 'm.rbs.generic.role-publisher', array('ucf')),
				'Administrator' => new I18nString($i18n, 'm.rbs.generic.role-administrator', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_PermissionRoles', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addPermissionPrivileges(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$modelsNames = $documentServices->getModelManager()->getModelsNames();
			$collection = array_combine($modelsNames, $modelsNames);
			$collection['*'] = new I18nString($i18n, 'm.rbs.generic.any-privilege', array('ucf'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_PermissionPrivileges', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}


	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addTimeZones(\Zend\EventManager\Event $event)
	{
		$items = array();
		$now = new \DateTime();
		foreach (\DateTimeZone::listIdentifiers() as $timeZoneName)
		{
			$now = new \DateTime('now', new \DateTimeZone($timeZoneName));
			$items[$timeZoneName] = $timeZoneName . ' (' . $now->format('P') .')';
		}

		$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_TimeZones', $items);
		$event->setParam('collection', $collection);
		$event->stopPropagation();
	}


	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addLanguages(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$items = array();
			$applicationServices = $documentServices->getApplicationServices();
			foreach ($applicationServices->getI18nManager()->getSupportedLCIDs() as $lcid)
			{
				$items[$lcid] = \Locale::getDisplayLanguage($lcid, $applicationServices->getI18nManager()->getLCID()) . ' (' . $lcid . ')';
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_Languages', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

}
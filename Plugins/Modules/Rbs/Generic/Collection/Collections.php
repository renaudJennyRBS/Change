<?php
namespace Rbs\Generic\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Generic\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addSortDirections(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'asc' => new I18nString($i18n, 'm.rbs.generic.front.ascending', array('ucf')),
				'desc' => new I18nString($i18n, 'm.rbs.generic.front.descending', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_SortDirections', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addPermissionRoles(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'*' => new I18nString($i18n, 'm.rbs.generic.admin.any_role', array('ucf')),
				'Consumer' => new I18nString($i18n, 'm.rbs.generic.admin.role_consumer', array('ucf')),
				'Creator' => new I18nString($i18n, 'm.rbs.generic.admin.role_creator', array('ucf')),
				'Editor' => new I18nString($i18n, 'm.rbs.generic.admin.role_editor', array('ucf')),
				'Publisher' => new I18nString($i18n, 'm.rbs.generic.admin.role_publisher', array('ucf')),
				'Administrator' => new I18nString($i18n, 'm.rbs.generic.admin.role_administrator', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_PermissionRoles', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addPermissionPrivileges(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$modelsNames = $applicationServices->getModelManager()->getModelsNames();
			$collection = array_combine($modelsNames, $modelsNames);
			$collection['*'] = new I18nString($i18n, 'm.rbs.generic.admin.any_privilege', array('ucf'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_PermissionPrivileges', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}


	/**
	 * @param \Change\Events\Event $event
	 */
	public function addTimeZones(\Change\Events\Event $event)
	{
		$items = array();
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
	 * @param \Change\Events\Event $event
	 */
	public function addLanguages(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$items = array();
			foreach ($applicationServices->getI18nManager()->getSupportedLCIDs() as $lcid)
			{
				$items[$lcid] = \Locale::getDisplayLanguage($lcid, $applicationServices->getI18nManager()->getLCID());
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_Languages', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAddressFields(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{

			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_AddressFields');

			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
				->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();

			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['id']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_AddressFields', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addShippingModes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{

			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Shipping_Mode');

			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
				->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();

			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['id']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_ShippingModes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

}
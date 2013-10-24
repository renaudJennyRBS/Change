<?php
namespace Rbs\Elasticsearch\Collection;

use Change\Documents\Query\Query;
use Change\I18n\I18nString;


/**
 * @name \Rbs\Elasticsearch\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addClients(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
			$indexManager->setDocumentServices($documentServices);

			$items = array();
			foreach ($indexManager->getClientsName() as $clientName)
			{
				$items[$clientName] = $clientName;
				$client = $indexManager->getClient($clientName);
				if ($client)
				{
					try
					{
						$serverStatus = $client->getStatus()->getServerStatus();

						if (isset($serverStatus['ok']) && $serverStatus['ok'])
						{
							$items[$clientName] .= ' (' . $serverStatus['name'] .', ' . $serverStatus['version']['number'] . ')';
						}
						else
						{
							$items[$clientName] .= ' (' . print_r($serverStatus, true). ')';
						}
					}
					catch (\Exception $e)
					{
						$items[$clientName] .= ' (' . $e->getMessage(). ')';
					}
				}
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_Clients', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addCollectionCodes(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$docQuery = new \Change\Documents\Query\Query($documentServices, 'Rbs_Collection_Collection');
			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('code'), 'code'))
					->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();
			$items = $query->getResults($query->getRowsConverter()->addStrCol('code', 'label')->indexBy('code'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_CollectionCodes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAttributeIds(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$docQuery = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_Attribute');
			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->where($fb->notIn($docQuery->getColumn('valueType'), array($fb->string('Text'), $fb->string('Group'))));

			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
					->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))
				->query();
			$items = $query->getResults($query->getRowsConverter()->addStrCol('label')->addIntCol('id')->indexBy('id'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_AttributeIds', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addIndexes(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
			$indexManager->setDocumentServices($documentServices);
			$query = new Query($documentServices, 'Rbs_Elasticsearch_FullText');
			$query->andPredicates($query->activated());
			$items = array();
			/* @var $indexDefinition \Rbs\Elasticsearch\Documents\FullText */
			foreach ($query->getDocuments() as $indexDefinition)
			{
				$items[$indexDefinition->getId()] = $indexDefinition->getLabel();
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_Indexes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
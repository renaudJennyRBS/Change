<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Collection;

/**
 * @name \Rbs\Elasticsearch\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Generic\GenericServices
	 */
	protected function getGenericServices(\Change\Events\Event $event)
	{
		$genericServices = $event->getServices('genericServices');
		return $genericServices;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addClients(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$indexManager = $this->getGenericServices($event)->getIndexManager();
			$items = [];
			foreach ($indexManager->getClientsName() as $clientName)
			{
				$items[$clientName] = $clientName;
				$client = $indexManager->getElasticaClient($clientName);
				if ($client)
				{
					try
					{
						$serverStatus = $client->getStatus()->getServerStatus();
						if (isset($serverStatus['status']) && $serverStatus['status'] == 200)
						{
							$items[$clientName] .= ' (' . $serverStatus['name'] . ', ' . $serverStatus['version']['number'] . ')';
						}
						else
						{
							$items[$clientName] .= ' (' . print_r($serverStatus, true) . ')';
						}
					}
					catch (\Exception $e)
					{
						$items[$clientName] .= ' (' . $e->getMessage() . ')';
					}
				}
			}

			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_Clients', $items);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addCollectionIds(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
				->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();
			$items = $query->getResults($query->getRowsConverter()->addIntCol('id')->addStrCol('label')->indexBy('id'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_CollectionIds', $items);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeIds(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute');
			$docQuery->andPredicates($docQuery->notIn('valueType', ['Text' ,'Group', 'DocumentIdArray']));
			$docQuery->addOrder('label');

			$items = [];
			/** @var $attr \Rbs\Catalog\Documents\Attribute */
			foreach ($docQuery->getDocuments() as $attr)
			{
				if ($attr->getValueType() === 'Property')
				{
					$property = $attr->getModelProperty();
					if ($property && in_array($property->getType(), ['Boolean', 'Integer', 'Float', 'Decimal',
							'DateTime', 'Date', 'String', 'DocumentId', 'Document']))
					{
						$items[$attr->getId()] = $attr->getLabel();
					}
				}
				else
				{
					$items[$attr->getId()] = $attr->getLabel();
				}
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_AttributeIds', $items);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addIndexes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_Index');
			$query->andPredicates($query->activated());
			$items = [];
			/* @var $indexDefinition \Rbs\Elasticsearch\Documents\Index */
			foreach ($query->getDocuments() as $indexDefinition)
			{
				$items[$indexDefinition->getId()] = $indexDefinition->composeRestLabel($applicationServices->getI18nManager());
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_Indexes', $items);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFacetConfigurationType(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$items = [];
			$items['Attribute'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_configuration_type_attribute', ['ucf']);
			$items['Price'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_configuration_type_price', ['ucf']);
			$items['SkuThreshold'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_configuration_type_sku_threshold', ['ucf']);
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_FacetConfigurationType', $items);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFacetRenderingModes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$items = [];
			$items['radio'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_rendering_modes_radio', ['ucf']);
			$items['checkbox'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_rendering_modes_checkbox', ['ucf']);
			if ($event->getParam('forType') == 'price')
			{
				$items['interval'] = $i18nManager->trans('m.rbs.elasticsearch.admin.facet_rendering_modes_interval', ['ucf']);
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_FacetRenderingModes', $items);
			$event->setParam('collection', $collection);
		}
	}
}
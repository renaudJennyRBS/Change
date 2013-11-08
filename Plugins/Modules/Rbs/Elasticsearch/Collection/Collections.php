<?php
namespace Rbs\Elasticsearch\Collection;

use Change\Documents\Query\Query;
use Rbs\Elasticsearch\Facet\FacetDefinitionInterface;

/**
 * @name \Rbs\Elasticsearch\Collection\Collections
 */
class Collections
{

	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Elasticsearch\ElasticsearchServices
	 */
	protected function getElasticsearchServices(\Change\Events\Event $event)
	{
		$elasticsearchServices = $event->getServices('elasticsearchServices');
		return $elasticsearchServices;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addClients(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$indexManager = $this->getElasticsearchServices($event)->getIndexManager();
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
	 * @param \Change\Events\Event $event
	 */
	public function addCollectionCodes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
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
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeIds(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute');
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
	 * @param \Change\Events\Event $event
	 */
	public function addIndexes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_FullText');
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

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFacetTypes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$items = array();
			$items[FacetDefinitionInterface::TYPE_TERM] = $i18nManager->trans('m.rbs.elasticsearch.documents.facet.type-term');
			$items[FacetDefinitionInterface::TYPE_RANGE] = $i18nManager->trans('m.rbs.elasticsearch.documents.facet.type-range');
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_FacetTypes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFacetValueExtractor(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$items = array();
			$items['Attribute'] = $i18nManager->trans('m.rbs.elasticsearch.documents.facet.value-extractor-attribute');
			$items['Price'] = $i18nManager->trans('m.rbs.elasticsearch.documents.facet.value-extractor-price');
			$items['SkuThreshold'] = $i18nManager->trans('m.rbs.elasticsearch.documents.facet.value-extractor-sku-threshold');
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_FacetValueExtractor', $items);
			$event->setParam('collection', $collection);
		}
	}
}
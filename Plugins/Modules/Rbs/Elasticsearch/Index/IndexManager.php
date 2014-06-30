<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

use Change\Documents\AbstractDocument;
use Elastica\Document;

/**
 * @name \Rbs\Elasticsearch\Index\IndexManager
 */
class IndexManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Elasticsearch_IndexManager';

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Elastica\Client[]
	 */
	protected $clients = [];

	/**
	 * @var array
	 */
	protected $clientsConfiguration = null;

	/**
	 * @var array
	 */
	protected $clientIndexes = [];

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Elasticsearch/Events/IndexManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getIndexesDefinition', [$this, 'onDefaultGetIndexesDefinition'], 5);
	}

	/**
	 * @api
	 * @param array $clientsConfiguration
	 */
	public function setClientsConfiguration(array $clientsConfiguration)
	{
		$this->clientsConfiguration = $clientsConfiguration;
	}

	/**
	 * @api
	 * @return array
	 */
	protected function getClientsConfiguration()
	{
		if ($this->clientsConfiguration === null)
		{
			$config = $this->getConfiguration()->getEntry('Rbs/Elasticsearch/clients');
			if (!is_array($config))
			{
				$config = array();
			}
			$this->setClientsConfiguration($config);
		}
		return $this->clientsConfiguration;
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getClientsName()
	{
		return array_keys($this->getClientsConfiguration());
	}

	/**
	 * @api
	 * @param string $clientName
	 * @return \Elastica\Client|null
	 */
	public function getElasticaClient($clientName)
	{
		$clientsNames = $this->getClientsName();
		if ($clientName === null)
		{
			$clientName = count($clientsNames) ? $clientsNames[0] : null;
		}
		elseif (!in_array($clientName, $clientsNames))
		{
			$clientName = null;
		}

		if ($clientName)
		{
			if (!array_key_exists($clientName, $this->clients))
			{
				$this->clients[$clientName] = null;

				$config = $this->clientsConfiguration[$clientName];
				try
				{
					if (is_array($config) && count($config))
					{
						$this->clients[$clientName] = new \Elastica\Client($config);
					}
				}
				catch (\Exception $e)
				{
					//Invalid Client configuration
					$this->getLogging()->exception($e);
				}
			}
			return $this->clients[$clientName];
		}

		return null;
	}

	/**
	 * @api
	 * @param string $clientName
	 * @return IndexDefinitionInterface[]
	 */
	public function getIndexesDefinition($clientName = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['indexesDefinition' => [], 'clientName' => $clientName]);
		$em->trigger('getIndexesDefinition', $this, $args);
		$indexesDefinition = $args['indexesDefinition'];
		return is_array($indexesDefinition) ? $indexesDefinition : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetIndexesDefinition(\Change\Events\Event $event)
	{
		/** @var $indexesDefinition array */
		$indexesDefinition = $event->getParam('indexesDefinition');

		$clientName = $event->getParam('clientName');

		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_Index');
		if ($clientName)
		{
			$query->andPredicates($query->activated(), $query->eq('clientName', $clientName));
		}
		else
		{
			$query->andPredicates($query->activated());
		}

		/** @var $indexDefinition \Rbs\Elasticsearch\Documents\Index */
		foreach ($query->getDocuments() as $indexDefinition)
		{
			$indexesDefinition[] = $indexDefinition;
		}
		$event->setParam('indexesDefinition', $indexesDefinition);
	}

	/**
	 * @api
	 * @param string $clientName
	 * @param string $indexName
	 * @return IndexDefinitionInterface[null
	 */
	public function findIndexDefinitionByName($clientName, $indexName)
	{
		$indexesDefinition = $this->getIndexesDefinition($clientName);
		foreach ($indexesDefinition as $indexDefinition)
		{
			if ($indexDefinition->getName() == $indexName)
			{
				return $indexDefinition;
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	public function deleteIndex($indexDefinition)
	{
		$client = $this->getElasticaClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$index->delete();
			return $index;
		}
		return null;
	}

	/**
	 * @api
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	public function createIndex($indexDefinition)
	{
		$client = $this->getElasticaClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$index->create($indexDefinition->getConfiguration(), true);
			return $index;
		}
		return null;
	}

	/**
	 * @api
	 * @param IndexDefinitionInterface $indexDefinition
	 * @param array $facetsMappings
	 * @return \Elastica\Index|null
	 */
	public function updateFacetsMappings($indexDefinition, array $facetsMappings)
	{
		$client = $this->getElasticaClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			if ($index && $index->exists())
			{
				foreach ($facetsMappings as $indexType => $mappings)
				{
					$typeMapping = \Elastica\Type\Mapping::create($mappings);
					$typeMapping->setParam('ignore_conflicts', true);
					$index->getType($indexType)->setMapping($typeMapping);
				}
			}
			return $index;
		}
		return null;
	}

	/**
	 * @api
	 * @param string $category
	 * @param string $analysisLCID
	 * @param array $propertyFilters
	 * @return \Rbs\Elasticsearch\Documents\Index|null
	 */
	public function getIndexByCategory($category, $analysisLCID, array $propertyFilters = null)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_Index');
		$query->andPredicates($query->activated(), $query->eq('category', $category), $query->eq('analysisLCID', $analysisLCID));
		if (!$propertyFilters)
		{
			return $query->getFirstDocument();
		}

		/** @var $index \Rbs\Elasticsearch\Documents\Index */
		foreach ($query->getDocuments() as $index)
		{
			$match = true;
			foreach ($propertyFilters as $propertyName => $propertyValue)
			{
				$property = $index->getDocumentModel()->getProperty($propertyName);
				if (!$property)
				{
					$match = false;
					break;
				}
				$value = $property->getValue($index);
				if ($value !== $propertyValue)
				{
					$match = false;
					break;
				}
			}
			if ($match)
			{
				return $index;
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Website\Documents\Website|integer $website
	 * @param string $analysisLCID
	 * @return \Rbs\Elasticsearch\Documents\FullText|null
	 */
	public function getFulltextIndexByWebsite($website, $analysisLCID)
	{
		$index = $this->getIndexByCategory('fulltext', $analysisLCID, ['website' => $website]);
		if ($index instanceof \Rbs\Elasticsearch\Documents\FullText)
		{
			return $index;
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Website\Documents\Website|integer $website
	 * @param string $analysisLCID
	 * @return \Rbs\Elasticsearch\Documents\StoreIndex|null
	 */
	public function getStoreIndexByWebsite($website, $analysisLCID)
	{
		$index = $this->getIndexByCategory('store', $analysisLCID, ['website' => $website]);
		if ($index instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
		{
			return $index;
		}
		return null;
	}


	/**
	 * @var array|null
	 */
	protected $clientBulks;

	/**
	 * @var IndexDefinitionInterface[]
	 */
	protected $bulkIndexes;

	/**
	 * @var array
	 */
	protected $bulkDocumentIds = [];

	/**
	 * @api
	 */
	public function startBulk()
	{
		$this->clientBulks = [];
		if ($this->bulkIndexes === null)
		{
			$this->bulkIndexes = [];
			foreach ($this->getIndexesDefinition() as $index)
			{
				if ($this->ensureIndexExist($index))
				{
					$this->bulkIndexes[] = $index;
				}
			}
		}
	}

	/**
	 * @api
	 * @param array $toIndex
	 */
	public function documentsBulkIndex($toIndex)
	{
		if (!count($this->getClientsName()))
		{
			return;
		}

		$this->startBulk();
		$modelManager = $this->getDocumentManager()->getModelManager();
		foreach ($toIndex as $data)
		{
			if (!is_array($data) || !isset($data['id']) || !isset($data['model']) || !isset($data['LCID']))
			{
				continue;
			}
			$modelName = $data['model'];
			$id = intval($data['id']);
			$LCID = $data['LCID'];
			$model = $modelManager->getModelByName($modelName);
			if ($model)
			{
				$this->documentBulkIndex($model, $id, $LCID);
			}
		}
		$this->sendBulk();
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractModel $model
	 * @param integer $id
	 * @param string|null $LCID
	 * @throws \RuntimeException
	 */
	public function documentBulkIndex(\Change\Documents\AbstractModel $model, $id, $LCID)
	{
		if ($this->clientBulks === null)
		{
			throw new \RuntimeException('bulk not started.', 999999);
		}

		$LCID = ($model->isLocalized()) ? $LCID : null;
		$dm = $this->getDocumentManager();
		$document = $dm->getDocumentInstance($id, $model);

		foreach ($this->bulkIndexes as $index)
		{
			$analysisLCID = $index->getAnalysisLCID();
			if ($LCID === null || $LCID === $analysisLCID)
			{
				$key = $id . '.' . $analysisLCID . '.' . $index->getName();
				if (isset($this->bulkDocumentIds[$key]))
				{
					continue;
				}
				$this->bulkDocumentIds[$key] = true;

				try
				{
					$dm->pushLCID($analysisLCID);
					if ($document)
					{
						$data = $index->getDocumentIndexData($this, $document);
					}
					else
					{
						$data = $index->getDocumentIndexData($this, $id, $model);
					}
					$dm->popLCID();
				}
				catch (\Exception $e)
				{
					$dm->popLCID();
					$this->getApplication()->getLogging()->exception($e);
					$data = [];
				}

				if ($data)
				{
					foreach ($data as $type => $documentData)
					{
						if ($documentData)
						{
							$elasticaDocument = new Document($id, $documentData, $type, $index->getName());
							$this->documentToAdd($index, $elasticaDocument);
						}
						else
						{
							$this->documentIdToDelete($index, $id, $type);
						}
					}
				}
			}
		}
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function sendBulk()
	{
		if ($this->clientBulks === null)
		{
			throw new \RuntimeException('bulk not started.', 999999);
		}

		/* @var $bulk \Elastica\Bulk */
		foreach ($this->clientBulks as $bulk)
		{
			if ($bulk)
			{
				$bulk->send();
			}
		}
		$this->clientBulks = null;
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @param Document $elasticaDocument
	 */
	protected function documentToAdd(IndexDefinitionInterface $indexDefinition, $elasticaDocument)
	{
		$clientName = $indexDefinition->getClientName();
		if (!array_key_exists($clientName, $this->clientBulks))
		{
			$client = $this->getElasticaClient($clientName);
			if ($client)
			{
				$this->clientBulks[$clientName] = new \Elastica\Bulk($client);
			}
			else
			{
				$this->clientBulks[$clientName] = false;
			}
		}

		$bulk = $this->clientBulks[$clientName];
		if ($bulk instanceof \Elastica\Bulk)
		{
			$bulk->addDocument($elasticaDocument);
		}
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @param string $id
	 * @param string|\Elastica\Type $type
	 */
	protected function documentIdToDelete(IndexDefinitionInterface $indexDefinition, $id, $type = null)
	{
		$clientName = $indexDefinition->getClientName();
		$indexName = $indexDefinition->getName();
		if (!array_key_exists($clientName, $this->clientBulks))
		{
			$client = $this->getElasticaClient($clientName);
			if ($client)
			{
				$this->clientBulks[$clientName] = new \Elastica\Bulk($client);
			}
			else
			{
				$this->clientBulks[$clientName] = false;
			}
		}

		$bulk = $this->clientBulks[$clientName];
		if ($bulk instanceof \Elastica\Bulk)
		{
			$action = (new \Elastica\Bulk\Action(\Elastica\Bulk\Action::OP_TYPE_DELETE))->setId($id)->setIndex($indexName);
			if ($type !== null)
			{
				$action->setType($type);
			}
			$bulk->addAction($action);
		}
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return boolean
	 */
	protected function ensureIndexExist(IndexDefinitionInterface $indexDefinition)
	{
		$clientName = $indexDefinition->getClientName();
		$indexName = $indexDefinition->getName();
		if (!isset($this->clientIndexes[$clientName][$indexName]))
		{
			$this->clientIndexes[$clientName][$indexName] = false;
			try
			{
				$client = $this->getElasticaClient($clientName);
				if (!$client->getStatus()->indexExists($indexName))
				{
					$index = $this->createIndex($indexDefinition);
					if ($index && $index->exists())
					{
						$this->clientIndexes[$clientName][$indexName] = true;
					}
				}
				else
				{
					$this->clientIndexes[$clientName][$indexName] = true;
				}
			}
			catch (\Exception $e)
			{
				//Unable to create Index
				$this->getLogging()->exception($e);
			}
		}
		return $this->clientIndexes[$clientName][$indexName];
	}
}
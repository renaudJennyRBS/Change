<?php
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
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Elastica\Client[]
	 */
	protected $clients = array();

	/**
	 * @var array
	 */
	protected $clientsConfiguration = null;

	/**
	 * @var array
	 */
	protected $clientBulks = array();

	/**
	 * @var array
	 */
	protected $clientIndexes = array();

	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetManager
	 */
	protected $facetManager;

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetManager $facetManager
	 * @return $this
	 */
	public function setFacetManager($facetManager)
	{
		$this->facetManager = $facetManager;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetManager
	 */
	public function getFacetManager()
	{
		return $this->facetManager;
	}

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 * @return $this
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 * @return $this
	 */
	public function setLogging($logging)
	{
		$this->logging = $logging;
		return $this;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->logging;
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Elasticsearch/Events/IndexManager');
	}

	/**
	 * @param array $clientsConfiguration
	 */
	public function loadConfiguration(array $clientsConfiguration)
	{
		$this->clientsConfiguration = $clientsConfiguration;
	}

	/**
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
			$this->loadConfiguration($config);
		}

		return $this->clientsConfiguration;
	}

	/**
	 * @return string[]
	 */
	public function getClientsName()
	{
		return array_keys($this->getClientsConfiguration());
	}

	/**
	 * @param string $clientName
	 * @return \Elastica\Client|null
	 */
	public function getClient($clientName)
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
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	protected function createIndex($indexDefinition)
	{
		$client = $this->getClient($indexDefinition->getClientName());
		$index = $client->getIndex($indexDefinition->getName());
		$index->create($indexDefinition->getConfiguration());
		return $index;
	}

	/**
	 * @param string $clientName
	 * @param Document $document
	 */
	public function documentToAdd($clientName, $document)
	{
		if (!array_key_exists($clientName, $this->clientBulks))
		{
			$client = $this->getClient($clientName);
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
			$indexName = $document->getIndex();
			if ($this->ensureIndexExist($clientName, $indexName))
			{
				$bulk->addDocument($document);
			}
		}
	}

	/**
	 * @param string $clientName
	 * @param string $indexName
	 * @param string $id
	 */
	public function documentIdToDelete($clientName, $indexName, $id)
	{
		if (!array_key_exists($clientName, $this->clientBulks))
		{
			$client = $this->getClient($clientName);
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
			if ($this->ensureIndexExist($clientName, $indexName))
			{
				$bulk->addAction((new \Elastica\Bulk\Action(\Elastica\Bulk\Action::OP_TYPE_DELETE))->setId($id)->setIndex($indexName));
			}
		}
	}

	/**
	 * @param $clientName
	 * @param $indexName
	 * @return boolean
	 */
	protected function ensureIndexExist($clientName, $indexName)
	{
		if (!isset( $this->clientIndexes[$clientName][$indexName]))
		{
			$this->clientIndexes[$clientName][$indexName] = false;

			try
			{
				$client = $this->getClient($clientName);
				if (!$client->getStatus()->indexExists($indexName))
				{
					$def = $this->findIndexDefinitionByName($clientName, $indexName);
					if ($def)
					{
						$index = $this->createIndex($def);
						if ($index->exists())
						{
							$this->clientIndexes[$clientName][$indexName] = true;
						}
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

	/**
	 * @param array $toIndex
	 */
	public function dispatchIndexationEvents($toIndex)
	{
		if (!count($this->getClientsName()))
		{
			return;
		}
		$this->clientBulks = array();

		$dm = $this->getDocumentManager();
		$mm = $dm->getModelManager();

		foreach ($toIndex as $data)
		{
			//$data ['LCID' => string, 'id' => integer, 'model' => string , 'deleted' => boolean
			$LCID = $data['LCID'];
			$id = $data['id'];
			$modelName = $data['model'];
			$model = $mm->getModelByName($modelName);
			$data['model'] = $model;

			if ($model)
			{
				$data['document'] = $dm->getDocumentInstance($id, $model);
			}

			try
			{
				$dm->pushLCID($LCID);
				$em = $this->getEventManager();
				$event = new Event(Event::INDEX_DOCUMENT, $this, $data);
				$em->trigger($event);
				$dm->popLCID();
			}
			catch (\Exception $e)
			{
				$dm->popLCID($e);
			}
		}

		/* @var $bulk \Elastica\Bulk */
		foreach ($this->clientBulks as $bulk)
		{
			if ($bulk)
			{
				$bulk->send();
			}
		}

		$this->clientBulks = array();
	}

	/**
	 * @param Document $elasticaDocument
	 * @param AbstractDocument $document
	 * @param IndexDefinitionInterface $indexDefinition
	 * @param array $parameters
	 */
	public function dispatchPopulateDocument(Document $elasticaDocument, AbstractDocument $document, $indexDefinition, array $parameters = null)
	{
		$em = $this->getEventManager();
		$params = $em->prepareArgs($parameters === null ? array() : $parameters);
		$params['elasticaDocument'] = $elasticaDocument;
		$params['document'] = $document;
		$params['indexDefinition'] = $indexDefinition;
		$event = new Event(Event::POPULATE_DOCUMENT, $this, $params);
		$em->trigger($event);
	}

	/**
	 * @param string $mappingName
	 * @param string $analysisLCID
	 * @param array $options
	 * @return null|IndexDefinitionInterface
	 */
	public function findIndexDefinitionByMapping($mappingName, $analysisLCID, array $options = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$args['mappingName'] = $mappingName;
		$args['analysisLCID'] = $analysisLCID;
		$event = new Event(Event::FIND_INDEX_DEFINITION, $this, $args);
		$em->trigger($event);
		$indexDefinition = $event->getParam('indexDefinition');
		return $indexDefinition instanceof IndexDefinitionInterface ? $indexDefinition : null;
	}

	/**
	 * @param string $clientName
	 * @param string $indexName
	 * @param array $options
	 * @return null|IndexDefinitionInterface
	 */
	public function findIndexDefinitionByName($clientName, $indexName, array $options = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$args['clientName'] = $clientName;
		$args['indexName'] = $indexName;
		$event = new Event(Event::FIND_INDEX_DEFINITION, $this, $args);
		$em->trigger($event);
		$indexDefinition = $event->getParam('indexDefinition');
		return $indexDefinition instanceof IndexDefinitionInterface ? $indexDefinition : null;
	}

	/**
	 * @param string $clientName
	 * @return IndexDefinitionInterface[]
	 */
	public function getIndexesDefinition($clientName = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('indexesDefinition' => array(), 'clientName' => $clientName));
		$event = new Event(Event::GET_INDEXES_DEFINITION, $this, $args);
		$em->trigger($event);
		$indexesDefinition = $event->getParam('indexesDefinition');
		return is_array($indexesDefinition) ? $indexesDefinition : array();
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	public function deleteIndex($indexDefinition)
	{
		$client = $this->getClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$index->delete();
			return $index;
		}
		return null;
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	public function setIndexConfiguration($indexDefinition)
	{
		$client = $this->getClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$index->create($indexDefinition->getConfiguration(), true);
			return $index;
		}
		return null;
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @param array $mapping
	 * @return \Elastica\Index|null
	 */
	public function setFacetMapping($indexDefinition, $mapping)
	{
		$client = $this->getClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$typeMapping = \Elastica\Type\Mapping::create($mapping);
			$typeMapping->setParam('ignore_conflicts', true);
			$index->getType($indexDefinition->getDefaultTypeName())->setMapping($typeMapping);
			return $index;
		}
		return null;
	}
}
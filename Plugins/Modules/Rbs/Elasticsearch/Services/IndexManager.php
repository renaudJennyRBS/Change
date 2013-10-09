<?php
namespace Rbs\Elasticsearch\Services;

use Change\Documents\AbstractDocument;
use Elastica\Document;
use Rbs\Elasticsearch\Events\Event;

/**
 * @name \Rbs\Elasticsearch\Services\IndexManager
 */
class IndexManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Elasticsearch_IndexManager';

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Elastica\Client[]
	 */
	protected $clients = array();

	/**
	 * @var \Elastica\Index[]
	 */
	protected $indexCollection = array();

	/**
	 * @var array
	 */
	protected $clientsConfiguration = null;

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->applicationServices === null)
		{
			$this->setApplicationServices($documentServices->getApplicationServices());
		}
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
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
		if ($this->applicationServices)
		{
			$config = $this->applicationServices->getApplication()->getConfiguration();
			return $config->getEntry('Rbs/Elasticsearch/Events/IndexManager', array());
		}
		return array();
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
			$config = $this->getApplicationServices()->getApplication()->getConfiguration()
				->getEntry('Rbs/Elasticsearch/clients');
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
		$client = null;
		if ($clientName === null)
		{
			$names = $this->getClientsName();
			$clientName = count($names) ? $names[0] : null;
		}

		if (is_string($clientName))
		{
			if (!isset($this->clients[$clientName]))
			{
				$config = $this->getApplicationServices()->getApplication()->getConfiguration()
					->getEntry('Rbs/Elasticsearch/clients/' . $clientName, []);
				if (is_array($config) && count($config))
				{
					$client = $this->initConnection($clientName, $config);
				}
			}
			else
			{
				$client = $this->clients[$clientName];
			}
		}

		return $client;
	}

	/**
	 * @param string $clientName
	 * @param array $config
	 * @return \Elastica\Client
	 */
	protected function initConnection($clientName, $config)
	{
		$this->clients[$clientName] = new \Elastica\Client($config);
		return $this->clients[$clientName];
	}

	/**
	 * @param \Rbs\Elasticsearch\Std\IndexDefinitionInterface $indexDefinition
	 * @return \Elastica\Index|null
	 */
	public function createIndex($indexDefinition)
	{
		$client = $this->getClient($indexDefinition->getClientName());
		if ($client)
		{
			$index = $client->getIndex($indexDefinition->getName());
			$index->create($indexDefinition->getConfiguration());
			return $index;
		}
		return null;
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

		$mm = $this->getDocumentServices()->getModelManager();
		$dm = $this->getDocumentServices()->getDocumentManager();
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
	}

	/**
	 * @param Document $elasticaDocument
	 * @param AbstractDocument $document
	 * @param \Rbs\Elasticsearch\Std\IndexDefinitionInterface $indexDefinition
	 */
	public function dispatchPopulateDocument(Document $elasticaDocument, AbstractDocument $document, $indexDefinition)
	{
		$em = $this->getEventManager();
		$event = new Event(Event::POPULATE_DOCUMENT, $this,
			array('elasticaDocument' => $elasticaDocument, 'document' => $document, 'indexDefinition' => $indexDefinition));
		$em->trigger($event);
	}

	/**
	 * @param string $mappingName
	 * @param string $analysisLCID
	 * @param array $options
	 * @return null|\Rbs\Elasticsearch\Std\IndexDefinitionInterface
	 */
	public function findIndexDefinition($mappingName, $analysisLCID, array $options = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$args['mappingName'] = $mappingName;
		$args['analysisLCID'] = $analysisLCID;
		$event = new Event(Event::FIND_INDEX_DEFINITION, $this, $args);
		$em->trigger($event);
		$indexDefinition = $event->getParam('indexDefinition');
		return $indexDefinition instanceof \Rbs\Elasticsearch\Std\IndexDefinitionInterface ? $indexDefinition : null;
	}
}
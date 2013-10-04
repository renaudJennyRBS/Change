<?php
namespace Rbs\Elasticsearch\Services;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Localizable;
use Elastica\Document;
use Rbs\Elasticsearch\Events\Event;

/**
 * @name \Rbs\Elasticsearch\Services\IndexManager
 */
class IndexManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const INDEX_FRONT = 'front';
	const INDEX_ADMIN = 'admin';

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
			$config = $this->getApplicationServices()->getApplication()->getConfiguration()->getEntry('Rbs/Elasticsearch/clients');
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
	 * @return \Elastica\Client
	 * @throws \InvalidArgumentException
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
				$config = $this->getApplicationServices()->getApplication()->getConfiguration()->getEntry('Rbs/Elasticsearch/clients/' . $clientName, []);
				if (is_array($config) && count($config))
				{
					$this->initConnection($clientName, $config);
				}
				else
				{
					throw new \InvalidArgumentException('Argument 1 should be a valid client configuration', 999999);
				}
			}
			$client = $this->clients[$clientName];
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a valid client name', 999999);
		}
		return $client;
	}

	/**
	 * @param string $clientName
	 * @param array $config
	 */
	protected function initConnection($clientName, $config)
	{
		$this->clients[$clientName] = new \Elastica\Client($config);
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	public function getMappingByName($name)
	{
		$em = $this->getEventManager();
		$event = new Event(Event::MAPPING_BY_NAME, $this, array('name' => $name));
		$em->trigger($event);
		$mapping = $event->getParam('mapping');
		if (is_array($mapping))
		{
			return $mapping;
		}
		return null;
	}

	/**
	 * @param string $LCID
	 * @return array|null
	 */
	public function getAnalyzerByLCID($LCID)
	{
		$em = $this->getEventManager();
		$event = new Event(Event::ANALYZER_BY_LCID, $this, array('LCID' => $LCID));
		$em->trigger($event);
		$analyzer = $event->getParam('analyzer');
		if (is_array($analyzer))
		{
			return $analyzer;
		}
		return null;
	}

	/**
	 * @param \Elastica\Index $index
	 * @param string $mappingName
	 * @param string $LCID
	 */
	public function createIndex($index, $mappingName, $LCID)
	{
		$indexConfig = [
			'mappings' => $this->getMappingByName($mappingName),
			'settings' => ['index' => ['analysis' => $this->getAnalyzerByLCID($LCID)]]
		];
		$index->create($indexConfig);
	}

	/**
	 * @param array $toIndex
	 */
	public function dispatchIndexationEvents($toIndex)
	{
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
	 * @param string $mapping
	 */
	public function dispatchPopulateDocument(Document $elasticaDocument, AbstractDocument $document, $mapping)
	{
		$em = $this->getEventManager();
		$event = new Event(Event::POPULATE_DOCUMENT, $this,
			array('elasticaDocument' => $elasticaDocument, 'document' => $document, 'mapping' => $mapping));
		$em->trigger($event);
	}
}
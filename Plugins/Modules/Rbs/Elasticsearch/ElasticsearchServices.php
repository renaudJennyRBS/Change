<?php
namespace Rbs\Elasticsearch;

use Change\Application;
use Change\Events\EventManagerFactory;
use Change\Services\ApplicationServices;

/**
* @name \Rbs\Elasticsearch\ElasticsearchServices
*/
class ElasticsearchServices extends \Zend\Di\Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}
	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Elasticsearch/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param EventManagerFactory $eventManagerFactory
	 * @param ApplicationServices $applicationServices
	 */
	function __construct(Application $application, EventManagerFactory $eventManagerFactory, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setEventManagerFactory($eventManagerFactory);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//FacetManager : EventManagerFactory, DocumentManager, I18nManager, CollectionManager
		$facetManagerClassName = $this->getInjectedClassName('FacetManager', '\Rbs\Elasticsearch\Facet\FacetManager');
		$classDefinition = $this->getClassDefinition($facetManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true))
			->addMethod('setI18nManager', true)
				->addMethodParameter('setI18nManager', 'i18nManager', array('required' => true))
			->addMethod('setCollectionManager', true)
				->addMethodParameter('setCollectionManager', 'collectionManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//IndexManager : FacetManager, EventManagerFactory, Configuration, DocumentManager, Logging
		$indexManagerClassName = $this->getInjectedClassName('IndexManager', 'Rbs\Elasticsearch\Index\IndexManager');
		$classDefinition = $this->getClassDefinition($indexManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$this->addConfigurationClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setFacetManager', true)
				->addMethodParameter('setFacetManager', 'facetManager', array('type' => 'FacetManager', 'required' => true))
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true))
			->addMethod('setLogging', true)
				->addMethodParameter('setLogging', 'logging', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$im = $this->instanceManager();

		$documentManager = function() use ($applicationServices) {return $applicationServices->getDocumentManager();};
		$i18nManager = function() use ($applicationServices) {return $applicationServices->getI18nManager();};
		$collectionManager = function() use ($applicationServices) {return $applicationServices->getCollectionManager();};
		$logging = function() use ($applicationServices) {return $applicationServices->getLogging();};
		$configuration = $application->getConfiguration();

		$im->addAlias('FacetManager', $facetManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'documentManager' => $documentManager,
				'collectionManager' => $collectionManager, 'i18nManager' => $i18nManager));

		$im->addAlias('IndexManager', $indexManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'configuration' => $configuration,
				'documentManager' => $documentManager, 'logging' => $logging));
	}

	/**
	 * @return \Rbs\Elasticsearch\Index\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->get('IndexManager');
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetManager
	 */
	public function getFacetManager()
	{
		return $this->get('FacetManager');
	}
} 
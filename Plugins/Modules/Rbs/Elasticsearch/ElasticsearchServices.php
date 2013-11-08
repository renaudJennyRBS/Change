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
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
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
	function __construct(Application $application, EventManagerFactory $eventManagerFactory,
		ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setEventManagerFactory($eventManagerFactory);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//FacetManager : EventManagerFactory, Application, ApplicationServices
		$facetManagerClassName = $this->getInjectedClassName('FacetManager', '\Rbs\Elasticsearch\Facet\FacetManager');
		$classDefinition = $this->getDefaultClassDefinition($facetManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//IndexManager : EventManagerFactory, Application, ApplicationServices, FacetManager
		$indexManagerClassName = $this->getInjectedClassName('IndexManager', 'Rbs\Elasticsearch\Index\IndexManager');
		$classDefinition = $this->getDefaultClassDefinition($indexManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setFacetManager', true)
			->addMethodParameter('setFacetManager', 'facetManager', array('type' => 'FacetManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$im = $this->instanceManager();

		$defaultParameters = array('application' => $this->getApplication(),
			'applicationServices' => $this->getApplicationServices(),
			'eventManagerFactory' => $this->getEventManagerFactory());

		$im->addAlias('FacetManager', $facetManagerClassName, $defaultParameters);

		$im->addAlias('IndexManager', $indexManagerClassName, $defaultParameters);
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
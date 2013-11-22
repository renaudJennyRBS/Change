<?php
namespace Rbs\Generic;

use Change\Application;
use Change\Events\EventManagerFactory;
use Change\Services\ApplicationServices;

/**
 * @name \Rbs\Generic\GenericServices
 */
class GenericServices extends \Zend\Di\Di
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
		$classes = $this->getApplication()->getConfiguration('Rbs/Generic/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param EventManagerFactory $eventManagerFactory
	 * @param ApplicationServices $applicationServices
	 */
	public function __construct(Application $application, EventManagerFactory $eventManagerFactory, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setEventManagerFactory($eventManagerFactory);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//SeoManager : EventManagerFactory, DocumentManager, TransactionManager
		$seoManagerClassName = $this->getInjectedClassName('SeoManager', 'Rbs\Seo\SeoManager');
		$classDefinition = $this->getClassDefinition($seoManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//AvatarManager : EventManagerFactory
		$avatarManagerClassName = $this->getInjectedClassName('AvatarManager', 'Rbs\Media\Avatar\AvatarManager');
		$classDefinition = $this->getClassDefinition($avatarManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//FieldManager : EventManagerFactory, ConstraintsManager
		$fieldManagerClassName = $this->getInjectedClassName('FieldManager', 'Rbs\Simpleform\Field\FieldManager');
		$classDefinition = $this->getClassDefinition($fieldManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setConstraintsManager', true)
			->addMethodParameter('setConstraintsManager', 'constraintsManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//SecurityManager : EventManagerFactory
		$securityManagerClassName = $this->getInjectedClassName('SecurityManager', 'Rbs\Simpleform\Security\SecurityManager');
		$classDefinition = $this->getClassDefinition($securityManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

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

		$transactionManager = function() use ($applicationServices) {return $applicationServices->getTransactionManager();};
		$documentManager = function() use ($applicationServices) {return $applicationServices->getDocumentManager();};
		$i18nManager = function() use ($applicationServices) {return $applicationServices->getI18nManager();};
		$collectionManager = function() use ($applicationServices) {return $applicationServices->getCollectionManager();};
		$logging = function() use ($applicationServices) {return $applicationServices->getLogging();};
		$configuration = $application->getConfiguration();

		$im->addAlias('SeoManager', $seoManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory,
				'documentManager' => $documentManager, 'transactionManager' => $transactionManager));

		$im->addAlias('AvatarManager', $avatarManagerClassName,  array('eventManagerFactory' => $this->getEventManagerFactory()));

		$constraintsManager = function () use ($applicationServices)
		{
			return $applicationServices->getConstraintsManager();
		};
		$im->addAlias('FieldManager', $fieldManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'constraintsManager' => $constraintsManager
		));

		$im->addAlias('SecurityManager', $securityManagerClassName, array(
			'eventManagerFactory' => $eventManagerFactory
		));

		$im->addAlias('FacetManager', $facetManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'documentManager' => $documentManager,
				'collectionManager' => $collectionManager, 'i18nManager' => $i18nManager));

		$im->addAlias('IndexManager', $indexManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'configuration' => $configuration,
				'documentManager' => $documentManager, 'logging' => $logging));
	}

	/**
	 * @api
	 * @return \Rbs\Seo\SeoManager
	 */
	public function getSeoManager()
	{
		return $this->get('SeoManager');
	}

	/**
	 * @api
	 * @return \Rbs\Media\Avatar\AvatarManager
	 */
	public function getAvatarManager()
	{
		return $this->get('AvatarManager');
	}

	/**
	 * @api
	 * @return \Rbs\Simpleform\Field\FieldManager
	 */
	public function getFieldManager()
	{
		return $this->get('FieldManager');
	}

	/**
	 * @api
	 * @return \Rbs\Simpleform\Security\SecurityManager
	 */
	public function getSecurityManager()
	{
		return $this->get('SecurityManager');
	}

	/**
	 * @api
	 * @return \Rbs\Elasticsearch\Index\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->get('IndexManager');
	}

	/**
	 * @api
	 * @return \Rbs\Elasticsearch\Facet\FacetManager
	 */
	public function getFacetManager()
	{
		return $this->get('FacetManager');
	}
}
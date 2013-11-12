<?php
namespace Change\Services;

/**
 * @name \Change\Services\ServicesCapableTrait
 */
trait ServicesCapableTrait
{
	/**
	 * @var \Change\Events\EventManagerFactory
	 */
	protected $eventManagerFactory;

	/**
	 * @var array<alias => className>
	 */
	protected $injectionClasses = null;

	/**
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return $this
	 */
	public function setEventManagerFactory(\Change\Events\EventManagerFactory $eventManagerFactory)
	{
		$this->eventManagerFactory = $eventManagerFactory;
		return $this;
	}

	/**
	 * @return \Change\Events\EventManagerFactory
	 */
	protected function getEventManagerFactory()
	{
		return $this->eventManagerFactory;
	}

	/**
	 * @return array<alias => className>
	 */
	abstract protected function loadInjectionClasses();

	/**
	 * @param string $alias
	 * @param string $defaultClassName
	 * @return string
	 */
	protected function getInjectedClassName($alias, $defaultClassName)
	{
		if ($this->injectionClasses === null)
		{
			$this->injectionClasses = $this->loadInjectionClasses();
		}
		$injectionClasses = $this->injectionClasses;
		if (isset($injectionClasses[$alias]))
		{
			if (class_exists($injectionClasses[$alias]))
			{
				return $injectionClasses[$alias];
			}
		}
		return $defaultClassName;
	}

	/**
	 * @param string $className
	 * @return \Zend\Di\Definition\ClassDefinition
	 */
	protected function getClassDefinition($className)
	{
		$classDefinition = new \Zend\Di\Definition\ClassDefinition($className);
		$classDefinition->setInstantiator('__construct');
		return $classDefinition;
	}

	/**
	 * setApplication($application)
	 * setApplicationServices($applicationServices)
	 * @param string $className
	 * @return \Zend\Di\Definition\ClassDefinition
	 */
	protected function getDefaultClassDefinition($className)
	{
		$classDefinition = $this->getClassDefinition($className)
			->addMethod('setApplication', true)
			->addMethodParameter('setApplication', 'application',
				array('type' => 'Change\Application', 'required' => true))
			->addMethod('setApplicationServices', true)
			->addMethodParameter('setApplicationServices', 'applicationServices',
				array('type' => 'Change\Services\ApplicationServices', 'required' => true));
		return $classDefinition;
	}

	/**
	 * setConfiguration($configuration)
	 * setWorkspace($workspace)
	 * @param string $className
	 * @return \Zend\Di\Definition\ClassDefinition
	 */
	protected function getConfigAndWorkspaceClassDefinition($className)
	{
		$classDefinition = $this->getClassDefinition($className);
		$this->addConfigurationClassDefinition($classDefinition)->addWorkspaceClassDefinition($classDefinition);
		return $classDefinition;
	}

	/**
	 * setConfiguration($configuration)
	 * @param \Zend\Di\Definition\ClassDefinition $classDefinition
	 * @return $this
	 */
	protected function addConfigurationClassDefinition(\Zend\Di\Definition\ClassDefinition $classDefinition)
	{
		$classDefinition->addMethod('setConfiguration', true)
			->addMethodParameter('setConfiguration', 'configuration',
				array('type' => 'Change\Configuration\Configuration', 'required' => true));
		return $this;
	}

	/**
	 * setWorkspace($workspace)
	 * @param \Zend\Di\Definition\ClassDefinition $classDefinition
	 * @return $this
	 */
	protected function addWorkspaceClassDefinition(\Zend\Di\Definition\ClassDefinition $classDefinition)
	{
		$classDefinition->addMethod('setWorkspace', true)
			->addMethodParameter('setWorkspace', 'workspace', array('type' => 'Change\Workspace', 'required' => true));
		return $this;
	}

	/**
	 * setEventManagerFactory($eventManagerFactory)
	 * @param \Zend\Di\Definition\ClassDefinition $classDefinition
	 * @return $this
	 */
	protected function addEventsCapableClassDefinition(\Zend\Di\Definition\ClassDefinition $classDefinition)
	{
		$classDefinition->addMethod('setEventManagerFactory', true)
			->addMethodParameter('setEventManagerFactory', 'eventManagerFactory',
				array('type' => 'Change\Events\EventManagerFactory', 'required' => true));
		return $this;
	}
}
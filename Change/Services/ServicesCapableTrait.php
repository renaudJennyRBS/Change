<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Services;

/**
 * @name \Change\Services\ServicesCapableTrait
 */
trait ServicesCapableTrait
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

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
	 * @var array<alias => className>
	 */
	protected $injectionClasses = null;


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
	 * @deprecated
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
	 * @deprecated
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
	 * @deprecated
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
	 * @deprecated
	 * @param \Zend\Di\Definition\ClassDefinition $classDefinition
	 * @return $this
	 */
	protected function addEventsCapableClassDefinition(\Zend\Di\Definition\ClassDefinition $classDefinition)
	{
		return $this->addApplicationClassDefinition($classDefinition);
	}

	/**
	 * setApplication(application)
	 * @param \Zend\Di\Definition\ClassDefinition $classDefinition
	 * @return $this
	 */
	protected function addApplicationClassDefinition(\Zend\Di\Definition\ClassDefinition $classDefinition)
	{
		$classDefinition->addMethod('setApplication', true)
			->addMethodParameter('setApplication', 'application', array('type' => 'Change\Application', 'required' => true));
		return $this;
	}
}
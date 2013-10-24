<?php
namespace Change\Services;

/**
 * @name \Change\Services\ServicesCapableTrait
 */
trait ServicesCapableTrait
{
	use DefaultServicesTrait;

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
			$this->getApplicationServices()->getLogging()->warn('Injection class: ' . $injectionClasses[$alias] . ' not found.');
		}
		return $defaultClassName;
	}

	/**
	 * @param string $className
	 * @return \Zend\Di\Definition\ClassDefinition
	 */
	protected function getDefaultClassDefinition($className)
	{
		$classDefinition = new \Zend\Di\Definition\ClassDefinition($className);
		$classDefinition->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
			->addMethodParameter('setApplicationServices', 'applicationServices',
				array('type' => 'Change\Application\ApplicationServices', 'required' => true))

			->addMethod('setDocumentServices', true)
			->addMethodParameter('setDocumentServices', 'documentServices',
				array('type' => 'Change\Documents\DocumentServices', 'required' => true));
		return $classDefinition;
	}
}
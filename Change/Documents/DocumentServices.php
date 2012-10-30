<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentServices
 */
class DocumentServices extends \Compilation\Change\Documents\AbstractDocumentServices
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	
		$dl = new \Zend\Di\DefinitionList(array());
		
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\ModelManager');
		$cl->setInstantiator('__construct');
		$dl->addDefinition($cl);
		
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\DocumentManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)
				->addMethodParameter('__construct', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true))
				->addMethodParameter('__construct', 'modelManager', array('type' => 'Change\Documents\ModelManager', 'required' => true));
		$dl->addDefinition($cl);
		
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\DocumentI18nSynchronizer');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)
				->addMethodParameter('__construct', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true))
				->addMethodParameter('__construct', 'documentManager', array('type' => 'Change\Documents\DocumentManager', 'required' => true));
		$dl->addDefinition($cl);
		
		parent::__construct($dl, $applicationServices);
		
		$this->instanceManager()->setInjections('Change\Documents\DocumentManager', array('Change\Documents\ModelManager'));
		$this->instanceManager()->setInjections('Change\Documents\DocumentI18nSynchronizer', array('Change\Documents\DocumentManager'));
	}
	
	/**
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->get('Change\Documents\ModelManager');
	}
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->get('Change\Documents\DocumentManager', array('applicationServices' => $this->applicationServices, 'documentServices' => $this));
	}
	
	/**
	 * @return \Change\Documents\DocumentI18nSynchronizer
	 */
	public function getDocumentI18nSynchronizer()
	{
		return $this->get('Change\Documents\DocumentI18nSynchronizer', array('applicationServices' => $this->applicationServices, 'documentServices' => $this));
	}
}
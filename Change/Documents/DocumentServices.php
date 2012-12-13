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
				->addMethodParameter('__construct', 'documentServices', array('type' => '\Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
		

		parent::__construct($dl, $applicationServices);
		$im = $this->instanceManager();
		$im->setParameters('Change\Documents\DocumentManager', array('applicationServices'=> $this->applicationServices, 'documentServices' => $this));
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
}
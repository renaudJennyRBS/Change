<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentServices
 */
class DocumentServices extends \Compilation\Change\Documents\AbstractDocumentServices
{

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices)
	{
		$dl = new \Zend\Di\DefinitionList(array());
		
		$this->registerModelManager($dl);

		$this->registerDocumentManager($dl);

		$this->registerTreeManager($dl);

		$this->registerConstraintsManager($dl);

		parent::__construct($dl, $applicationServices);

		$im = $this->instanceManager();
		$im->setParameters('Change\Documents\DocumentManager', array('applicationServices'=> $applicationServices, 'documentServices' => $this));
		$im->setParameters('Change\Documents\TreeManager', array('applicationServices'=> $applicationServices, 'documentServices' => $this));
		$im->setParameters('Change\Documents\Constraints\ConstraintsManager', array('applicationServices'=> $applicationServices, 'documentServices' => $this));
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerModelManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\ModelManager');
		$cl->setInstantiator('__construct');
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerDocumentManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\DocumentManager');
		$cl->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
				->addMethodParameter('setApplicationServices', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true))
			->addMethod('setDocumentServices', true)
				->addMethodParameter('setDocumentServices', 'documentServices', array('type' => '\Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerTreeManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\TreeManager');
		$cl->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
				->addMethodParameter('setApplicationServices', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true))
			->addMethod('setDocumentServices', true)
				->addMethodParameter('setDocumentServices', 'documentServices', array('type' => '\Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerConstraintsManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\Constraints\ConstraintsManager');
		$cl->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
				->addMethodParameter('setApplicationServices', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true))
			->addMethod('setDocumentServices', true)
				->addMethodParameter('setDocumentServices', 'documentServices', array('type' => '\Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
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
		return $this->get('Change\Documents\DocumentManager');
	}
	
	/**
	 * @return \Change\Documents\TreeManager
	 */
	public function getTreeManager()
	{
		return $this->get('Change\Documents\TreeManager');
	}
	
	/**
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	public function getConstraintsManager()
	{
		return $this->get('Change\Documents\Constraints\ConstraintsManager');
	}

	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractService
	 */
	public function getByModel(\Change\Documents\AbstractModel $model)
	{
		/* @var $service \Change\Documents\AbstractService */
		$service = $this->get($model->getName());
		return $service;
	}
}
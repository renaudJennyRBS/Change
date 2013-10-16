<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentServices
 */
class DocumentServices extends \Zend\Di\Di
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
		
		$this->registerModelManager($dl);

		$this->registerDocumentManager($dl);

		$this->registerTreeManager($dl);

		$this->registerConstraintsManager($dl);

		parent::__construct($dl);

		$im = $this->instanceManager();
		$im->setParameters('Change\Documents\ModelManager', array('applicationServices'=> $applicationServices));

		$im->setParameters('Change\Documents\DocumentManager', array('applicationServices'=> $applicationServices, 'documentServices' => $this));

		$im->setInjections('Change\Documents\TreeManager', array('Change\Documents\DocumentManager'));
		$im->setParameters('Change\Documents\TreeManager', array('applicationServices'=> $applicationServices));
		$im->setParameters('Change\Documents\Constraints\ConstraintsManager', array('applicationServices'=> $applicationServices));
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerModelManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Documents\ModelManager');
		$cl->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
			->addMethodParameter('setApplicationServices', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true));
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
				->addMethodParameter('setDocumentManager', 'documentManager', array('type' => 'Change\Documents\DocumentManager', 'required' => true));
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
				->addMethodParameter('setApplicationServices', 'applicationServices', array('type' => 'Change\Application\ApplicationServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @api
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @api
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
	 * @api
	 * @return \Change\Documents\TreeManager
	 */
	public function getTreeManager()
	{
		return $this->get('Change\Documents\TreeManager');
	}
	
	/**
	 * @api
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	public function getConstraintsManager()
	{
		return $this->get('Change\Documents\Constraints\ConstraintsManager');
	}
}
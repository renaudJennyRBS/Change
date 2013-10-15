<?php
namespace Change\Services;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Zend\Di\Definition\ClassDefinition;
use Zend\Di\DefinitionList;
use Zend\Di\Di;

/**
* @name \Change\Services\CommonServices
*/
class CommonServices extends Di
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	function __construct(ApplicationServices $applicationServices, DocumentServices $documentServices)
	{
		$this->setApplicationServices($applicationServices);
		$this->setDocumentServices($documentServices);

		$dl = new DefinitionList(array());
		$this->registerCollectionManager($dl);
		$this->registerJobManager($dl);
		parent::__construct($dl);

		$im = $this->instanceManager();
		$im->setParameters('Change\Collection\CollectionManager', array('documentServices' => $this->documentServices));
		$im->setParameters('Change\Job\JobManager', array('applicationServices' => $this->applicationServices
				, 'documentServices' => $this->documentServices));
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}


	/**
	 * @param DefinitionList $dl
	 */
	protected function registerCollectionManager($dl)
	{
		$cl = new ClassDefinition('Change\Collection\CollectionManager');
		$cl->setInstantiator('__construct')
			->addMethod('setDocumentServices', true)
			->addMethodParameter('setDocumentServices', 'documentServices',
				array('type' => 'Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		return $this->get('Change\Collection\CollectionManager');
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerJobManager($dl)
	{
		$cl = new ClassDefinition('Change\Job\JobManager');
		$cl->setInstantiator('__construct')
			->addMethod('setApplicationServices', true)
			->addMethodParameter('setApplicationServices', 'applicationServices',
				array('type' => 'Change\Application\ApplicationServices', 'required' => true))
			->addMethod('setDocumentServices', true)
			->addMethodParameter('setDocumentServices', 'documentServices',
				array('type' => 'Change\Documents\DocumentServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @return \Change\Job\JobManager
	 */
	public function getJobManager()
	{
		return $this->get('Change\Job\JobManager');
	}
}
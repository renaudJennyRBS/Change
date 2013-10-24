<?php
namespace Rbs\Elasticsearch;

/**
* @name \Rbs\Elasticsearch\ElasticsearchServices
*/
class ElasticsearchServices extends \Zend\Di\Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Elasticsearch/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentServices $documentServices)
	{
		$this->setApplicationServices($applicationServices);
		$this->setDocumentServices($documentServices);

		$definitionList = new \Zend\Di\DefinitionList(array());
		$indexManagerClassName = $this->getInjectedClassName('IndexManager', 'Rbs\Elasticsearch\Services\IndexManager');
		$classDefinition = $this->getDefaultClassDefinition($indexManagerClassName);
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);
		$im = $this->instanceManager();

		$defaultParameters = array('applicationServices' => $this->getApplicationServices(),
			'documentServices' => $this->getDocumentServices());

		$im->addAlias('IndexManager', $indexManagerClassName, $defaultParameters);
	}

	/**
	 * @return \Rbs\Elasticsearch\Services\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->get('IndexManager');
	}
} 
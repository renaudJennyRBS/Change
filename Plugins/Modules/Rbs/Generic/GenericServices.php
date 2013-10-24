<?php
namespace Rbs\Generic;

/**
 * @name \Rbs\Generic\GenericServices
 */
class GenericServices extends \Zend\Di\Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Generic/Services');
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
		$seoManagerClassName = $this->getInjectedClassName('SeoManager', 'Rbs\Seo\SeoManager');
		$classDefinition = $this->getDefaultClassDefinition($seoManagerClassName);
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);
		$im = $this->instanceManager();

		$defaultParameters = array('applicationServices' => $this->getApplicationServices(),
			'documentServices' => $this->getDocumentServices());

		$im->addAlias('SeoManager', $seoManagerClassName, $defaultParameters);
	}

	/**
	 * @return \Rbs\Seo\SeoManager
	 */
	public function getSeoManager()
	{
		return $this->get('SeoManager');
	}
}
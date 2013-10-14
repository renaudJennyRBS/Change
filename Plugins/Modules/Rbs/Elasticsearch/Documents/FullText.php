<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\FullText
 */
class FullText extends \Compilation\Rbs\Elasticsearch\Documents\FullText implements \Rbs\Elasticsearch\Std\IndexDefinitionInterface
{
	/**
	 * @var \Rbs\Elasticsearch\Std\FacetDefinitionInterface[]
	 */
	protected $facetsDefinition;


	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getDocumentServices()->getApplicationServices()->getI18nManager()->trans('m.rbs.elasticsearch.documents.fulltext.label-webiste', array('ucf'), array('websiteLabel' => $this->getWebsite()->getLabel())); //$this->getName();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @return array
	 */
	public function getConfiguration()
	{
		$configuration = $this->getConfigurationData();
		return is_array($configuration) ? $configuration : array();
	}

	/**
	 * @return string
	 */
	public function getMappingName()
	{
		return 'fulltext';
	}

	protected function onCreate()
	{
		if (!$this->getName() && $this->getWebsiteId() && $this->getAnalysisLCID())
		{
			$this->setName($this->buildIndexNameForWebsiteAndLCID($this->getWebsiteId(), $this->getAnalysisLCID()));
		}

		if ($this->getName() && $this->getAnalysisLCID())
		{
			$configFile = dirname(__DIR__) . '/Assets/Config/fulltext_' . $this->getAnalysisLCID() . '.json';
			if (file_exists($configFile))
			{
				$config = \Zend\Json\Json::decode(file_get_contents($configFile), \Zend\Json\Json::TYPE_ARRAY);
				$this->setConfigurationData($config);
			}
		}
		$config = $this->getConfigurationData();
		if (is_array($config) && count($config))
		{
			$this->setActive(true);
		}
		else
		{
			$this->setActive(false);
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('configurationData'))
		{
			$config = $this->getConfigurationData();
			if (!is_array($config) || count($config) == 0)
			{
				$this->setActive(false);
			}
		}
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @return string
	 */
	protected function buildIndexNameForWebsiteAndLCID($websiteId, $LCID)
	{
		return  $this->getMappingName() . '_'. $websiteId  . '_' . strtolower($LCID);
	}

	/**
	 * @return \Rbs\Elasticsearch\Std\FacetDefinitionInterface[]
	 */
	public function getFacetsDefinition()
	{
		if ($this->facetsDefinition === null)
		{
			$mf = new \Rbs\Elasticsearch\Std\ModelFacetDefinition('model');
			$mf->setTitle($this->getApplicationServices()->getI18nManager()->trans('m.rbs.elasticsearch.fo.facet-model-title'));
			$this->facetsDefinition = array($mf);
		}
		return $this->facetsDefinition;
	}
}

<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\FullText
 */
class FullText extends \Compilation\Rbs\Elasticsearch\Documents\FullText
	implements \Rbs\Elasticsearch\Index\IndexDefinitionInterface
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getWebsite())
		{
			return $this->getApplicationServices()->getI18nManager()
				->trans('m.rbs.elasticsearch.documents.fulltext.label-website', array('ucf'),
					array('websiteLabel' => $this->getWebsite()->getLabel()));
		}
		return '';
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
		if (!$this->getName())
		{
			$this->setName($this->buildDefaultIndexName());
		}

		$config = $this->getConfigurationData();
		if (!is_array($config) || count($config) === 0)
		{
			$config = $this->buildDefaultConfiguration();
			$this->setConfigurationData($config);
		}

		if (count($config))
		{
			$this->setActive(true);
		}
		else
		{
			$this->setActive(false);
		}
	}

	public function resetConfiguration()
	{
		$config = $this->buildDefaultConfiguration();
		$this->setConfigurationData($config);
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('configurationData') && $this->getActive())
		{
			$config = $this->getConfigurationData();
			if (!is_array($config) || count($config) == 0)
			{
				$this->setActive(false);
			}
		}
	}

	/**
	 * @return string
	 */
	protected function buildDefaultIndexName()
	{
		return $this->getMappingName() . '_' . $this->getWebsiteId() . '_' . strtolower($this->getAnalysisLCID());
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	public function getFacetsDefinition()
	{
		$facetsDefinition = $this->getFacets()->toArray();
		if (count($facetsDefinition) === 0)
		{
			$facetsDefinition[] = $this->getDefaultModelFacet();
		}
		return $facetsDefinition;
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\ModelFacetDefinition
	 */
	protected function getDefaultModelFacet()
	{
		$mf = new \Rbs\Elasticsearch\Facet\ModelFacetDefinition('model');
		$mf->setTitle($this->getApplicationServices()->getI18nManager()->trans('m.rbs.elasticsearch.fo.facet-model-title'));
		return $mf;
	}

	/**
	 * @return array
	 */
	protected function buildDefaultConfiguration()
	{
		$config = array();
		if ($this->getAnalysisLCID())
		{
			$configFile =
				dirname(__DIR__) . '/Assets/Config/' . $this->getMappingName() . '_' . $this->getAnalysisLCID() . '.json';
			if (file_exists($configFile))
			{
				return \Zend\Json\Json::decode(file_get_contents($configFile), \Zend\Json\Json::TYPE_ARRAY);
			}
		}
		return $config;
	}
}

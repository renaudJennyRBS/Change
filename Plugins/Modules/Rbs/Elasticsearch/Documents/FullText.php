<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\FullText
 */
class FullText extends \Compilation\Rbs\Elasticsearch\Documents\FullText implements \Rbs\Elasticsearch\Std\IndexDefinitionInterface
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getName();
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
		return $this->getConfigurationData();
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
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @return string
	 */
	protected function buildIndexNameForWebsiteAndLCID($websiteId, $LCID)
	{
		return 'fulltext_'. strtolower($websiteId . '_' . $LCID);
	}
}

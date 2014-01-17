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
		return 'm.rbs.elasticsearch.documents.fulltext_label_website';
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

	/**
	 * @return string
	 */
	public function getDefaultTypeName()
	{
		return 'document';
	}

	public function buildLabel(\Change\I18n\I18nManager $i18nManager)
	{
		if ($this->getWebsite())
		{
			return $i18nManager->trans($this->getLabel(), array('ucf'),
				array('websiteLabel' => $this->getWebsite()->getLabel()));
		}
		return '';
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $document FullText */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$restResult->setProperty('label', $document->buildLabel($event->getApplicationServices()->getI18nManager()));
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$indexManager = $genericServices->getIndexManager();
				$client = $indexManager->getClient($document->getClientName());
				if ($client)
				{
					try
					{
						$status = $client->getStatus();
						$server = ['status' => $status->getServerStatus()];
						$index = $client->getIndex($document->getName());
						if ($index->exists())
						{
							$status = $index->getStatus();
							$server['index'] = ['doc' => $status->get('docs'), 'index' => $status->get('index')];
						}
					}
					catch (\Exception $e)
					{
						$server = ['error' => $e->getMessage()];
					}
					$restResult->setProperty('server', $server);
				}
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;
			$documentLink->setProperty('label', $document->buildLabel($event->getApplicationServices()->getI18nManager()));
		}
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
		$mf->setTitle(new \Change\I18n\PreparedKey('m.rbs.elasticsearch.fo.facet-model-title'));
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

	/**
	 * @return \Rbs\Elasticsearch\Documents\Facet
	 */
	public function getFacets()
	{
		return [];
	}

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facets
	 * @return $this
	 */
	public function setFacets($facets)
	{
		return $this;
	}
}

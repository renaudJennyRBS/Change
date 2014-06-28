<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Documents;

use Rbs\Elasticsearch\Index\IndexDefinitionInterface;

/**
 * @name \Rbs\Elasticsearch\Documents\Index
 */
abstract class Index extends \Compilation\Rbs\Elasticsearch\Documents\Index implements IndexDefinitionInterface
{

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	protected $facetsDefinition;


	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCategory() . '_' . $this->getAnalysisLCID();
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
		if ($this->configuration === null)
		{
			if ($this->getApplication()->inDevelopmentMode())
			{
				$this->configuration = $this->buildDefaultConfiguration();
			}
			else
			{
				$this->configuration = $this->getConfigurationData();
				if (!is_array($this->configuration)) {
					$this->configuration = [];
				}
			}

		}
		return $this->configuration;
	}

	/**
	 * @return array
	 */
	protected function buildDefaultConfiguration()
	{
		$config = [];
		if ($this->getAnalysisLCID())
		{
			$configFile = dirname(__DIR__) . '/Assets/Config/' . $this->getCategory() . '_' . $this->getAnalysisLCID() . '.json';
			if (file_exists($configFile))
			{
				return \Zend\Json\Json::decode(file_get_contents($configFile), \Zend\Json\Json::TYPE_ARRAY);
			}
		}
		return $config;
	}

	/**
	 * @return string
	 */
	public function getDefaultTypeName()
	{
		return 'document';
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return string
	 */
	abstract function composeRestLabel(\Change\I18n\I18nManager $i18nManager);

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	public function getFacetsDefinition()
	{
		if ($this->facetsDefinition === null)
		{
			$event = new \Change\Documents\Events\Event('getFacetsDefinition', $this, []);
			$this->getEventManager()->trigger($event);
			$facetsDefinition = $event->getParam('facetsDefinition');
			$this->facetsDefinition = is_array($facetsDefinition) ? $facetsDefinition : [];
		}

		return $this->facetsDefinition;
	}

	protected function onCreate()
	{
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

	protected $ignoredPropertiesForRestEvents = ['model', 'category'];

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if (!$genericServices)
		{
			return;
		}

		$indexManager = $genericServices->getIndexManager();

		$restResult = $event->getParam('restResult');

		/** @var $document Index */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$restResult->setProperty('label', $document->composeRestLabel($event->getApplicationServices()->getI18nManager()));
			$client = $indexManager->getElasticaClient($document->getClientName());
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
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$documentLink = $restResult;
			$documentLink->setProperty('label', $document->composeRestLabel($event->getApplicationServices()->getI18nManager()));
		}
	}
}

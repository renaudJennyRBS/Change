<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;


/**
 * @name \Rbs\Elasticsearch\Facet\FacetManager
 */
class FacetManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Elasticsearch_FacetManager';

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Elasticsearch/Events/FacetManager');
	}

	/**
	 * @api
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @return array
	 */
	public function getIndexMapping(\Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition)
	{
		$indexMapping = array();
		foreach ($indexDefinition->getFacetsDefinition() as $facet)
		{
			if ($facet instanceof FacetDefinitionInterface)
			{
				$mapping = $facet->getMapping();
				$indexMapping = $this->mergeFacetMapping($indexMapping, $mapping);
			}
		}
		return $indexMapping;
	}

	/**
	 * @api
	 * @param array $indexMapping
	 * @param array $facetMapping
	 * @return array
	 */
	public function mergeFacetMapping(array $indexMapping, array $facetMapping = null)
	{
		if ($facetMapping)
		{
			foreach ($facetMapping as $indexType => $mapping)
			{
				if (is_array($mapping) && count($mapping))
				{
					if (isset($indexMapping[$indexType]))
					{
						foreach ($mapping as $propertyName => $propertyConfig)
						{
							if (is_array($propertyConfig) && count($propertyConfig))
							{
								$indexMapping[$indexType][$propertyName] = $propertyConfig;
							}
						}
					}
					else
					{
						$indexMapping[$indexType] = $mapping;
					}
				}
			}
		}
		return $indexMapping;
	}

	/**
	 * @api
	 * @param integer[] $facetIds
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $index
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	public function resolveFacetIds(array $facetIds, \Rbs\Elasticsearch\Index\IndexDefinitionInterface $index = null)
	{
		$documentManager = $this->getDocumentManager();
		$facets = [];
		foreach ($facetIds as $facetId)
		{
			/** @var $facetDocument \Rbs\Elasticsearch\Documents\Facet */
			$facetDocument = $documentManager->getDocumentInstance($facetId, 'Rbs_Elasticsearch_Facet');
			if ($facetDocument)
			{
				if ($index === null || $facetDocument->getIndexCategory() == $index->getCategory())
				{
					$facet = $facetDocument->getFacetDefinition();
					if ($facet)
					{
						$facets[] = $facet;
					}
				}
			}
		}
		return $facets;
	}
}
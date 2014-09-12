<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\ModelFacetDefinition
 */
class ModelFacetDefinition implements FacetDefinitionInterface
{
	/**
	 * @var string
	 */
	protected $title = 'm.rbs.elasticsearch.front.facet_model_title';

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

	function __construct()
	{
		$this->getParameters()->set('multipleChoice', true);
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager($modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->modelManager;
	}


	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getI18nManager()->trans($this->title, ['ucf']);
	}

	/**
	 * @return string
	 */
	public function getFieldName()
	{
		return 'model';
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		if ($this->parameters === null)
		{
			$this->parameters = new \Zend\Stdlib\Parameters();
		}
		return $this->parameters;
	}

	/**
	 * @return null
	 */
	public function getParent()
	{
		return null;
	}

	/**
	 * @return boolean
	 */
	public function hasChildren()
	{
		return false;
	}

	/**
	 * @return FacetDefinitionInterface[]
	 */
	public function getChildren()
	{
		return [];
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array
	 */
	public function addIndexData($document, array $documentData)
	{
		return $documentData;
	}

	/**
	 * Part of index mapping
	 * @return array
	 */
	public function getMapping()
	{
		return [];
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter|null
	 */
	public function getFiltersQuery(array $facetFilters, array $context = [])
	{
		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]) && is_array($facetFilters[$filterName]))
		{
			$facetFilter =  $facetFilters[$filterName];
			$terms = [];
			foreach ($facetFilter as $key => $ignored)
			{
				$key = strval($key);
				if (!empty($key))
				{
					$terms[] = $key;
				}
			}

			if (count($terms))
			{
				return new \Elastica\Filter\Terms('model', $terms);
			}
		}
		return null;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$aggregation = new \Elastica\Aggregation\Terms('model');
		$aggregation->setField('model');
		return $aggregation;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		$mappingName = 'model';
		if (isset($aggregations[$mappingName]['buckets']))
		{
			foreach ($aggregations[$mappingName]['buckets'] as $bucket)
			{
				$modelName = $bucket['key'];
				$title = null;
				$model = $this->getModelManager()->getModelByName($modelName);
				if ($model)
				{
					$title = $this->getI18nManager()->trans($model->getLabelKey());
				}
				$v = new \Rbs\Elasticsearch\Facet\AggregationValue($modelName, $bucket['doc_count'], $title);
				$av->addValue($v);
			}
		}
		return $av;
	}


}
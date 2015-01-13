<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Documents;

use Rbs\Elasticsearch\Facet\FacetDefinitionInterface;
use Rbs\Elasticsearch\Facet\ProductAttributeFacetDefinition;
use Rbs\Elasticsearch\Facet\ProductPriceFacetDefinition;
use Rbs\Elasticsearch\Facet\ProductSkuThresholdFacetDefinition;

/**
 * @name \Rbs\Elasticsearch\Documents\Facet
 */
class Facet extends \Compilation\Rbs\Elasticsearch\Documents\Facet
{
	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|null;
	 */
	protected $parent;

	/**
	 * @var \Zend\Stdlib\Parameters|null
	 */
	protected $parameters;

	/**
	 * @var FacetDefinitionInterface|boolean|null
	 */
	protected $facetDefinition = false;

	/**
	 * Attach specific document event
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([\Change\Documents\Events\Event::EVENT_CREATE, \Change\Documents\Events\Event::EVENT_UPDATE],
			[$this, 'onDefaultSave'], 10);
		$eventManager->attach('getFacetDefinition', [$this, 'onDefaultGetFacetDefinition'], 5);
	}

	/**
	 * @param array $parameters
	 * @return $this
	 */
	public function setParameters($parameters = null)
	{
		$this->parameters = new \Zend\Stdlib\Parameters();
		if (is_array($parameters))
		{
			$this->parameters->fromArray($parameters);
		}
		elseif ($parameters instanceof \Traversable)
		{
			foreach ($parameters as $n => $v)
			{
				$this->parameters->set($n, $v);
			}
		}
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		if ($this->parameters === null)
		{
			$v = $this->getParametersData();
			$this->parameters = new \Zend\Stdlib\Parameters(is_array($v) ? $v : null);
		}
		return $this->parameters;
	}

	/**
	 * @param null|\Rbs\Elasticsearch\Facet\FacetDefinitionInterface $parent
	 * @return $this
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * @return null|\Rbs\Elasticsearch\Facet\FacetDefinitionInterface
	 */
	protected function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $facet Facet */
		$facet = $event->getDocument();
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$documentResult = $restResult;
			$documentResult->setProperty('parameters', $facet->getParametersData());
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$linkResult = $restResult;
			$linkResult->setProperty('facetsCount', $facet->getFacetsCount());
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch($name)
		{
			case 'parameters':
				$this->setParameters($value);
				break;

			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultSave(\Change\Documents\Events\Event $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		if ($this->isPropertyModified('parametersData') || $this->isPropertyModified('configurationType'))
		{
			switch ($this->getConfigurationType())
			{
				case 'Attribute':
					$facetDefinition = new ProductAttributeFacetDefinition($this);
					$facetDefinition->setDocumentManager($this->getDocumentManager());
					$facetDefinition->validateConfiguration($this);
					$this->saveWrappedProperties();
					break;
				case 'Price':
					$facetDefinition = new ProductPriceFacetDefinition($this);
					$facetDefinition->setDocumentManager($this->getDocumentManager());
					$facetDefinition->validateConfiguration($this);
					$this->saveWrappedProperties();
					break;
				case 'SkuThreshold':
					$facetDefinition = new ProductSkuThresholdFacetDefinition($this);
					$facetDefinition->setDocumentManager($this->getDocumentManager());
					$facetDefinition->validateConfiguration($this);
					$this->saveWrappedProperties();
					break;
			}
		}
	}

	public function saveWrappedProperties()
	{
		if ($this->parameters)
		{
			$parametersData = $this->parameters->toArray();
			$this->parameters = null;
			$this->setParametersData(count($parametersData) ? $parametersData : null);
		}
	}

	protected function onCreate()
	{
		$this->saveWrappedProperties();
	}

	protected function onUpdate()
	{
		$this->saveWrappedProperties();
	}


	/**
	 * @return FacetDefinitionInterface|null
	 */
	public function getFacetDefinition()
	{
		if ($this->facetDefinition === false)
		{
			$event = new \Change\Documents\Events\Event('getFacetDefinition', $this, []);
			$this->getEventManager()->trigger($event);
			$facetDefinition = $event->getParam('facetDefinition');
			$this->facetDefinition = $facetDefinition instanceof FacetDefinitionInterface ? $facetDefinition : null;
		}
		return $this->facetDefinition;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultGetFacetDefinition(\Change\Documents\Events\Event $event)
	{
		/** @var $facet \Rbs\Elasticsearch\Documents\Facet */
		$facet = $event->getDocument();

		$facetDefinition = $event->getParam('facetDefinition');

		$applicationServices = $event->getApplicationServices();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$facetDefinition)
		{
			switch ($facet->getConfigurationType())
			{
				case 'Attribute':
					$facetDefinition = new ProductAttributeFacetDefinition($facet);
					$facetDefinition->setCatalogManager($commerceServices->getCatalogManager());
					$event->setParam('facetDefinition', $facetDefinition);
					break;
				case 'Price':
					$facetDefinition = new ProductPriceFacetDefinition($facet);
					$facetDefinition->setI18nManager($applicationServices->getI18nManager());
					$event->setParam('facetDefinition', $facetDefinition);
					break;
				case 'SkuThreshold':
					$facetDefinition = new ProductSkuThresholdFacetDefinition($facet);
					break;
			}
		}

		if ($facetDefinition instanceof \Rbs\Elasticsearch\Facet\DocumentFacetDefinition)
		{
			$facetDefinition->setDocumentManager($applicationServices->getDocumentManager());
			$facetDefinition->setParent($facet->getParent());
			if ($facet->getFacetsCount())
			{
				$children = [];
				foreach ($facet->getFacets() as $child)
				{
					if (!$child->getParent())
					{
						$child->setParent($facetDefinition);
						$childFacetDefinition = $child->getFacetDefinition();
						if ($childFacetDefinition)
						{
							$children[] = $childFacetDefinition;
						}
						$child->setParent(null);
					}
				}
				$facetDefinition->setChildren($children);
			}
			$event->setParam('facetDefinition', $facetDefinition);
		}
	}
}

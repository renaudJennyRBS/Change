<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

use Rbs\Elasticsearch\Facet\FacetDefinitionInterface;

/**
 * @name \Rbs\Elasticsearch\Index\SearchQuery
 */
class SearchQuery
{
	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetManager
	 */
	protected $facetManager;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var IndexDefinitionInterface
	 */
	protected $indexDefinition;

	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	protected $validFacets;

	/**
	 * @var array
	 */
	protected $collections = array();

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 */
	function __construct(IndexDefinitionInterface $indexDefinition)
	{
		$this->indexDefinition = $indexDefinition;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetManager $facetManager
	 * @return $this
	 */
	public function setFacetManager(\Rbs\Elasticsearch\Facet\FacetManager $facetManager)
	{
		$this->facetManager = $facetManager;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetManager
	 */
	protected function getFacetManager()
	{
		return $this->facetManager;
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		if ($this->collectionManager === null)
		{
			throw new \RuntimeException('Collection manager not set.', 999999);
		}
		return $this->collectionManager;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager(\Change\I18n\I18nManager $i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		if ($this->i18nManager === null)
		{
			throw new \RuntimeException('I18n manager not set.', 999999);
		}
		return $this->i18nManager;
	}

	/**
	 * @param IndexDefinitionInterface $indexDefinition
	 * @return $this
	 */
	public function setIndexDefinition(IndexDefinitionInterface $indexDefinition)
	{
		$this->indexDefinition = $indexDefinition;
		return $this;
	}

	/**
	 * @return IndexDefinitionInterface
	 */
	public function getIndexDefinition()
	{
		return $this->indexDefinition;
	}

	/**
	 * @param string $searchText
	 * @param string $allowedSectionIds
	 * @param array $facetFilters
	 * @param integer $from
	 * @param integer $size
	 * @param array $fields
	 * @return \Elastica\Query
	 */
	public function getSearchQuery($searchText, $allowedSectionIds = null, $facetFilters = null, $from = 0, $size = 50,
		$fields = null)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		if ($searchText)
		{
			$multiMatch = new \Elastica\Query\MultiMatch();
			$multiMatch->setQuery($searchText);
			$multiMatch->setFields(array('title', 'content'));
		}
		else
		{
			$multiMatch = new \Elastica\Query\MatchAll();
		}

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
		}
		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);

		$facetFilters = $this->getFacetFilters($facetFilters);
		if ($facetFilters)
		{
			$query->setFilter($facetFilters);
		}

		if (is_array($fields))
		{
			$query->setFields($fields);
		}
		$query->setFrom($from)->setSize($size);
		return $query;
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param array $facetFilters
	 * @param integer $from
	 * @param integer $size
	 * @param array $fields
	 * @return \Elastica\Query
	 */
	public function getListSearchQuery($productList = null, $facetFilters = null, $from = 0, $size = 50, $fields = null)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		$multiMatch = new \Elastica\Query\MatchAll();
		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));
		if ($productList)
		{
			$nested = new \Elastica\Filter\Nested();
			$nested->setPath('listItems');
			$nestedBool = new \Elastica\Query\Bool();
			$nestedBool->addMust(new \Elastica\Query\Term(['listId' => $productList->getId()]));
			$nested->setQuery($nestedBool);
			$bool->addMust($nested);
		}

		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);
		if (is_array($fields))
		{
			$query->setFields($fields);
		}

		$facetFilters = $this->getFacetFilters($facetFilters);
		if ($facetFilters)
		{
			$query->setFilter($facetFilters);
		}
		$query->setFrom($from)->setSize($size);
		return $query;
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param array $facets
	 * @return \Elastica\Query
	 */
	public function getListFacetsQuery($productList = null, $facets = null)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		$multiMatch = new \Elastica\Query\MatchAll();

		$bool = new \Elastica\Filter\Bool();

		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));
		if ($productList)
		{
			$nested = new \Elastica\Filter\Nested();
			$nested->setPath('listItems');
			$nestedBool = new \Elastica\Query\Bool();
			$nestedBool->addMust(new \Elastica\Query\Term(['listId' => $productList->getId()]));
			$nested->setQuery($nestedBool);
			$bool->addMust($nested);
		}

		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);
		$query->setSize(0);

		if (is_array($facets))
		{
			$this->addFacets($query, $facets);
		}

		return $query;
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param FacetDefinitionInterface|string $facet
	 * @param array $facetFilters
	 * @return \Elastica\Query|null
	 */
	public function getListFacetQuery($productList = null, $facet, $facetFilters = null)
	{
		$facetQuery = $this->getFacet($facet);
		if ($facetQuery)
		{
			$now = (new \DateTime())->format(\DateTime::ISO8601);
			$multiMatch = new \Elastica\Query\MatchAll();

			$bool = new \Elastica\Filter\Bool();
			$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
			$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));
			if ($productList)
			{
				$nested = new \Elastica\Filter\Nested();
				$nested->setPath('listItems');
				$nestedBool = new \Elastica\Query\Bool();
				$nestedBool->addMust(new \Elastica\Query\Term(['listId' => $productList->getId()]));
				$nested->setQuery($nestedBool);
				$bool->addMust($nested);
			}

			$otherFacetFilters = $this->getOtherFacetFilters($facetQuery->getName(), $facetFilters);
			if ($otherFacetFilters)
			{
				foreach ($otherFacetFilters as $otherFacetFilter)
				{
					$filter = $this->getFacetManager()->getFilterQuery($otherFacetFilter['facet'], $otherFacetFilter['facetFilter']);
					if ($filter)
					{
						$bool->addMust($filter);
					}
				}
			}

			$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
			$query = new \Elastica\Query($filtered);
			$query->addFacet($facetQuery);
			$query->setSize(0);
			return $query;
		}
		return null;
	}

	/**
	 * @param string $collectionCode
	 * @return \Change\Collection\CollectionInterface|null
	 */
	public function getCollectionByCode($collectionCode)
	{
		if (!$collectionCode)
		{
			return null;
		}

		if (!array_key_exists($collectionCode, $this->collections))
		{
			$this->collections[$collectionCode] = $this->getCollectionManager()->getCollection($collectionCode);
		}
		return $this->collections[$collectionCode];
	}

	/**
	 * @param array $facetFilters
	 * @return \Elastica\Filter\Bool|null
	 */
	public function getFacetFilters($facetFilters)
	{
		if (is_array($facetFilters))
		{
			$indexDefinition = $this->getIndexDefinition();
			$must = array();
			foreach ($facetFilters as $facetName => $facetFilter)
			{
				if (is_string($facetFilter) && empty($facetFilter))
				{
					continue;
				}
				foreach ($indexDefinition->getFacetsDefinition() as $facet)
				{
					if ($facet->getFieldName() != $facetName)
					{
						continue;
					}

					$filter = $this->getFacetManager()->getFilterQuery($facet, $facetFilter);
					if ($filter)
					{
						$must[] = $filter;
					}
				}
			}

			if (count($must))
			{
				$bool = new \Elastica\Filter\Bool();
				foreach ($must as $m)
				{
					$bool->addMust($m);
				}
				return $bool;
			}
		}
		return null;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param FacetDefinitionInterface[]|string[] $facetsOrFacetNames
	 */
	public function addFacets(\Elastica\Query $query, array $facetsOrFacetNames = null)
	{
		if ($facetsOrFacetNames)
		{
			foreach ($facetsOrFacetNames as $facet)
			{
				$queryFacet = $this->getFacet($facet);
				if ($queryFacet)
				{
					$query->addFacet($queryFacet);
				}
			}
		}
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	protected function getValidFacets()
	{
		if ($this->validFacets === null)
		{
			$this->validFacets = array();
			foreach ($this->indexDefinition->getFacetsDefinition() as $facet)
			{
				$this->validFacets[$facet->getFieldName()] = $facet;
			}
		}
		return $this->validFacets;
	}

	/**
	 * @param string $facetName
	 * @param array $facetFilters
	 * @return array|null
	 */
	protected function getOtherFacetFilters($facetName, $facetFilters)
	{
		if (!is_array($facetFilters) || count($facetFilters) == 0)
		{
			return null;
		}

		$otherFacetFilters = [];
		$validFacets = $this->getValidFacets();
		if (isset($validFacets[$facetName]))
		{
			foreach ($facetFilters as $facetFilterName => $facetFilter)
			{
				if ($facetFilterName == $facetName ||
					!isset($validFacets[$facetFilterName]) ||
					(is_string($facetFilter) && empty($facetFilter)))
				{
					continue;
				}
				$otherFacetFilters[$facetFilterName] =  ['facet' => $validFacets[$facetFilterName], 'facetFilter' => $facetFilter];
			}

		}
		return count($otherFacetFilters) ? $otherFacetFilters : null;
	}

	/**
	 * @param FacetDefinitionInterface|string $facetOrFacetName
	 * @return \Elastica\Facet\Terms|\Elastica\Facet\Range|null
	 */
	public function getFacet($facetOrFacetName)
	{
		$facetName = ($facetOrFacetName instanceof
			FacetDefinitionInterface) ? $facetOrFacetName->getFieldName() : strval($facetOrFacetName);

		if ($facetName)
		{
			$validFacets = $this->getValidFacets();
			if (isset($validFacets[$facetName]))
			{
				return $this->getFacetManager()->getFacetQuery($validFacets[$facetName], []);
			}
		}
		return null;
	}

	/**
	 * @param FacetDefinitionInterface|string $facetOrFacetName
	 * @return null|FacetDefinitionInterface
	 */
	public function getValidFacetDefinition($facetOrFacetName)
	{
		$validFacets = $this->getValidFacets();
		if ($facetOrFacetName instanceof FacetDefinitionInterface)
		{
			return (isset($validFacets[$facetOrFacetName->getFieldName()])) ? $facetOrFacetName : null;
		}
		elseif (is_string($facetOrFacetName))
		{
			return (isset($validFacets[$facetOrFacetName])) ? $validFacets[$facetOrFacetName] : null;
		}
		return null;
	}

	/**
	 * @param array $facetResults
	 * @param array $facetFilters
	 * @param \Rbs\Elasticsearch\Documents\Facet[]|string[] $facets
	 * @return \Rbs\Elasticsearch\Facet\FacetValue[]
	 */
	public function buildFacetValues(array $facetResults, array $facetFilters, array $facets = null)
	{
		$facetManager = $this->getFacetManager();
		if ($facets === null)
		{
			$facets = array_keys($facetResults);
		}

		$facetValues = array();
		foreach ($facets as $facetOrFacetName)
		{
			$facet = $this->getValidFacetDefinition($facetOrFacetName);
			if (!$facet)
			{
				continue;
			}
			$facetName = $facet->getFieldName();
			$facetResult = isset($facetResults[$facetName]) ? $facetResults[$facetName] : null;
			$facetFilter = isset($facetFilters[$facetName]) ? $facetFilters[$facetName] : null;
			$values = $facetManager->buildFacetValues($facet, $facetResult, $facetFilter);

			$facetValues[$facetName] = $values;
		}
		return $facetValues;
	}
}
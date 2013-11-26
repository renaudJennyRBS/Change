<?php
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

		$bool = $this->getFacetFilters($facetFilters);
		if (!$bool)
		{
			$bool = new \Elastica\Filter\Bool();
		}
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
		}
		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);
		if (is_array($fields))
		{
			$query->setFields($fields);
		}
		$query->setFrom($from)->setSize($size);
		return $query;
	}

	/**
	 * @param string $searchText
	 * @param string $allowedSectionIds
	 * @param array $facetFilters
	 * @param array $facets
	 * @return \Elastica\Query
	 */
	public function getFacetsQuery($searchText, $allowedSectionIds = null, $facetFilters = null, $facets = null)
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

		$bool = $this->getFacetFilters($facetFilters);
		if (!$bool)
		{
			$bool = new \Elastica\Filter\Bool();
		}
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
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
					if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_TERM)
					{
						$must[] = new \Elastica\Filter\Terms($facetName, is_array($facetFilter) ? $facetFilter : array($facetFilter));
					}
					elseif (is_string($facetFilter))
					{
						$fromTo = explode('::', $facetFilter);
						if (count($fromTo) === 2)
						{
							$args = array();
							if ($fromTo[0])
							{
								$args['from'] = $fromTo[0];
							}
							if ($fromTo[1])
							{
								$args['to'] = $fromTo[1];
							}
							$must[] = new \Elastica\Filter\Range($facetName, $args);
						}
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
				$facet = $validFacets[$facetName];
			}
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_RANGE
			&& ($facet instanceof\Rbs\Elasticsearch\Documents\Facet)
		)
		{
			$collection = $this->getCollectionByCode($facet->getCollectionCode());
			if (!$collection)
			{
				return null;
			}

			$ranges = array();
			foreach ($collection->getItems() as $item)
			{
				$fromTo = explode('::', $item->getValue());
				if (count($fromTo) == 2)
				{
					$ranges[] = $fromTo;
				}
			}
			if (count($ranges))
			{
				$queryFacet = new \Elastica\Facet\Range($facet->getFieldName());
				$queryFacet->setField($facet->getFieldName());
				foreach ($ranges as $fromTo)
				{
					$queryFacet->addRange($fromTo[0] == '' ? null : $fromTo[0], $fromTo[1] == '' ? null : $fromTo[1]);
				}
				return $queryFacet;
			}
		}

		if ($facet->getFacetType() === FacetDefinitionInterface::TYPE_TERM)
		{
			$queryFacet = new \Elastica\Facet\Terms($facet->getFieldName());
			$queryFacet->setField($facet->getFieldName());
			return $queryFacet;
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
			if (!isset($facetResults[$facetName]))
			{
				continue;
			}
			$facetValueFiltered = false;

			$facetData = $facetResults[$facetName];
			if ($facetData['_type'] == 'terms')
			{
				foreach ($facetData['terms'] as $term)
				{
					if (($count = intval($term['count'])) == 0 && !$facet->getShowEmptyItem())
					{
						continue;
					}
					$value = $term['term'];
					$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
					$facetValue->setCount($count);
					if (is_array($facetFilters) && isset($facetFilters[$facetName]))
					{
						$facetFilter = $facetFilters[$facetName];
						if ((is_array($facetFilter) && in_array($value, $facetFilter))
							|| (is_string($facetFilter) && $facetFilter == $value)
						)
						{
							$facetValue->setFiltered(true);
							$facetValueFiltered = true;
						}
					}
					$facetValues[$facetName][] = $facetManager->updateFacetValueTitle($facetValue, $facet);
				}
			}
			else
			{
				foreach ($facetData['ranges'] as $range)
				{
					if (($count = intval($range['count'])) == 0 && !$facet->getShowEmptyItem())
					{
						continue;
					}
					$value = (isset($range['from']) ? $range['from'] : '') . '::' . (isset($range['to']) ? $range['to'] : '');
					$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue($value);
					$facetValue->setCount(intval($range['count']));
					if (is_array($facetFilters) && isset($facetFilters[$facetName]))
					{
						$facetFilter = $facetFilters[$facetName];
						if ((is_array($facetFilter) && in_array($value, $facetFilter))
							|| (is_string($facetFilter) && $facetFilter == $value)
						)
						{
							$facetValue->setFiltered(true);
							$facetValueFiltered = true;
						}
					}
					$facetValues[$facetName][] = $facetManager->updateFacetValueTitle($facetValue, $facet);
				}
			}

			if (!$facet->getParameters()->get(FacetDefinitionInterface::PARAM_MULTIPLE_CHOICE))
			{
				$facetValue = new \Rbs\Elasticsearch\Facet\FacetValue('');
				$facetValue->setValueTitle($this->getI18nManager()->trans('m.rbs.elasticsearch.front.ignore_facet', ['ucf']));
				if (!$facetValueFiltered)
				{
					$facetValue->setFiltered(true);
				}
				$facetValues[$facetName][] = $facetValue;
			}
		}
		return $facetValues;
	}
}
<?php
namespace Change\Documents\Query;

use Change\Documents\DocumentServices;
use Change\Db\Query\Predicates\Like;

/**
* @name \Change\Documents\Query\JSONDecoder
*/
class JSONDecoder
{
	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var AbstractBuilder[]
	 */
	protected $joins = array();

	/**
	 * @var Query
	 */
	protected $query;

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @throws \RuntimeException
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		if ($this->documentServices === null)
		{
			throw new \RuntimeException('DocumentServices not set');
		}
		return $this->documentServices;
	}

	/**
	 * @return Query
	 */
	public function getDocumentQuery()
	{
		return $this->query;
	}

	/**
	 * @param Query $documentQuery
	 */
	public function setDocumentQuery(Query $documentQuery)
	{
		$this->query = $documentQuery;
	}

	/**
	 * @param string $name
	 * @return AbstractBuilder|null
	 */
	public function getJoin($name)
	{
		return isset($this->joins[$name]) ? $this->joins[$name] : null;
	}

	/**
	 * @api
	 * @param string|array $json
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 * @return Query
	 */
	public function getQuery($json)
	{
		$jsonQuery = is_string($json) ? json_decode($json, true) : $json;
		if (!is_array($jsonQuery))
		{
			throw new \InvalidArgumentException('Argument is not a valid json query string', 999999);
		}

		if ($this->query === null)
		{

			if (isset($jsonQuery['model']) && is_string($jsonQuery['model']))
			{
				$model = $this->getDocumentServices()->getModelManager()->getModelByName($jsonQuery['model']);
			}
			else
			{
				$model = null;
			}

			if (!$model)
			{
				throw new \RuntimeException('Invalid Parameter: model', 71000);
			}
			$this->query = new Query($this->getDocumentServices(), $model);
		}

		$this->configureQuery($this->query, $jsonQuery);
		return $this->query;
	}

	/**
	 * @param Query $query
	 * @param array $jsonQuery
	 * @throws \RuntimeException
	 */
	public function configureQuery(Query $query, array $jsonQuery)
	{
		$this->joins = array();
		if (isset($jsonQuery['join']) && is_array($jsonQuery['join']))
		{
			foreach($jsonQuery['join'] as $joinJSON)
			{
				$this->processJoin($query, $joinJSON);
			}
		}

		if (isset($jsonQuery['where']) && is_array($jsonQuery['where']))
		{
			foreach($jsonQuery['where'] as $junction => $predicatesJSON)
			{
				$predicates = $this->configureJunction($query->getPredicateBuilder(), $junction, $predicatesJSON, false);
				if ($junction === 'and')
				{
					$query->andPredicates($predicates);
				}
				else
				{
					$query->orPredicates($predicates);
				}
			}
		}

		if (isset($jsonQuery['order']) && is_array($jsonQuery['order']))
		{
			foreach ($jsonQuery['order'] as $orderInfo)
			{
				if (isset($orderInfo['property']))
				{
					$asc = (!isset($orderInfo['order'])) || $orderInfo['order'] !== 'desc';

					if (isset($orderInfo['join']))
					{
						if (isset($this->joins[$orderInfo['join']]))
						{
							/* @var $pb AbstractBuilder */
							$pb = $this->joins[$orderInfo['join']];
							$pb->addOrder($orderInfo['property'], $asc);
						}
						else
						{
							throw new \RuntimeException('Invalid Query order', 999999);
						}
					}
					else
					{
						$query->addOrder($orderInfo['property'], $asc);
					}
				}
				else
				{
					throw new \RuntimeException('Invalid Query order', 999999);
				}
			}
		}
	}

	/**
	 * @return \Change\Documents\TreeManager
	 */
	protected function getTreeManager()
	{
		return $this->getDocumentServices()->getTreeManager();
	}

	/**
	 * @param AbstractBuilder $parentQuery
	 * @param array $joinJSON
	 * @throws \RuntimeException
	 */
	protected function processJoin(AbstractBuilder $parentQuery, array $joinJSON)
	{
		if (!isset($joinJSON['name']))
		{
			throw new \RuntimeException('Required Join name', 999999);
		}

		$joinName = $joinJSON['name'];
		if (isset($this->joins[$joinName]))
		{
			throw new \RuntimeException('Duplicate Join name: ' . $joinName, 999999);
		}

		if (!isset($joinJSON['model']))
		{
			throw new \RuntimeException('Required Join model', 999999);
		}
		$modelName = $joinJSON['model'];
		$model = $this->getDocumentServices()->getModelManager()->getModelByName($modelName);
		if ($model === null || $model->isStateless())
		{
			throw new \RuntimeException('Invalid Join model name: ' . $modelName, 999999);
		}
		$propertyName = !isset($joinJSON['property']) ? 'id' : $joinJSON['property'];
		$parentPropertyName = !isset($joinJSON['parentProperty']) ? 'id' : $joinJSON['parentProperty'];
		if ($propertyName === 'id' && $parentPropertyName === 'id')
		{
			throw new \RuntimeException('Required Join property / parentProperty', 999999);
		}
		$property = $model->getProperty($propertyName);
		if ($property === null || $property->getStateless())
		{
			throw new \RuntimeException('Invalid Join property: '. $propertyName, 999999);
		}

		$parentProperty = $parentQuery->getModel()->getProperty($parentPropertyName);
		if ($parentProperty === null || $parentProperty->getStateless())
		{
			throw new \RuntimeException('Invalid Join parentProperty: '. $parentPropertyName, 999999);
		}
		$childBuilder = $parentQuery->getPropertyModelBuilder($parentProperty, $model, $property);
		$this->joins[$joinName] = $childBuilder;

		if (isset($joinJSON['join']) && is_array($joinJSON['join']))
		{
			foreach($joinJSON['join'] as $childJoinJSON)
			{
				$this->processJoin($childBuilder, $childJoinJSON);
			}
		}
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param string $junction
	 * @param array $predicatesJSON
	 * @param boolean $asPredicate
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\Conjunction|\Change\Db\Query\Predicates\Disjunction|\Change\Db\Query\Predicates\InterfacePredicate[]
	 */
	protected function configureJunction($predicateBuilder, $junction, $predicatesJSON, $asPredicate = true)
	{
		if ($junction !== 'and' && $junction !== 'or')
		{
			throw new \RuntimeException('Invalid Query junction', 999999);
		}

		$predicates = array();
		foreach($predicatesJSON as $predicateJSON)
		{
			$predicates[] = $this->configurePredicate($predicateBuilder, $predicateJSON);
		}

		if ($asPredicate)
		{
			if ($junction === 'and')
			{
				return $predicateBuilder->logicAnd($predicates);
			}
			else
			{
				return $predicateBuilder->logicOr($predicates);
			}
		}
		return $predicates;
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configurePredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['or']))
		{
			return $this->configureJunction($predicateBuilder, 'or', $predicateJSON['or']);
		}
		elseif (isset($predicateJSON['and']))
		{
			return $this->configureJunction($predicateBuilder, 'and', $predicateJSON['and']);
		}
		elseif (isset($predicateJSON['op']))
		{
			$callable = array($this, 'configure' . ucfirst($predicateJSON['op']. 'Predicate'));
			if (is_callable($callable))
			{
				return call_user_func($callable, $predicateBuilder, $predicateJSON);
			}
			else
			{
				$argument = array('predicateJSON' => $predicateJSON,
					'JSONDecoder' => $this, 'predicateBuilder' => $predicateBuilder);
				$fragment = $this->query->getDbProvider()->getCustomSQLFragment($argument);
				if ($fragment)
				{
					return $fragment;
				}
			}
		}
		throw new \RuntimeException('Invalid predicate: ' . json_encode($predicateJSON), 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param $expressionJson
	 * @throws \RuntimeException
	 * @param array $expressionJson
	 * @return PredicateBuilder
	 */
	protected function getValidPredicateBuilder($predicateBuilder, $expressionJson)
	{
		if (isset($expressionJson['join']))
		{
			if (isset($this->joins[$expressionJson['join']]))
			{
				$abstractBuilder = $this->joins[$expressionJson['join']];
				return $abstractBuilder->getPredicateBuilder();
			}
			throw new \RuntimeException('Invalid join property: ' . $expressionJson['join'] . '.' . $expressionJson['property'] , 999999);
		}
		return $predicateBuilder;
	}

	/**
	 * Configure 'eq'
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureEqPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->eq($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid eq predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNeqPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->neq($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid neq predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureGtPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->gt($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid gt predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureGtePredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->gte($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid gte predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureLtPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->lt($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid lt predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureLtePredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->lte($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
		}
		throw new \RuntimeException('Invalid lte predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureLikePredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && isset($predicateJSON['rexp']['value']))
		{
			if (isset($predicateJSON['mode']))
			{
				if ($predicateJSON['mode'] === 'begin')
				{
					$matchMode =  Like::BEGIN;
				}
				elseif ($predicateJSON['mode'] === 'end')
				{
					$matchMode =  Like::END;
				}
				elseif ($predicateJSON['mode'] === 'any')
				{
					$matchMode =  Like::ANYWHERE;
				}
				else
				{
					throw new \RuntimeException('Invalid like mode predicate', 999999);
				}
			}
			else
			{
				$matchMode = Like::ANYWHERE;
			}
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->like($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value'], $matchMode);
		}
		throw new \RuntimeException('Invalid like predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureInPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && is_array($predicateJSON['rexp']) && isset($predicateJSON['rexp'][0]))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->in($predicateJSON['lexp']['property'], $predicateJSON['rexp']);
		}
		throw new \RuntimeException('Invalid in predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNotInPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['lexp']['property']) && is_array($predicateJSON['rexp']) && isset($predicateJSON['rexp'][0]))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['lexp'])
				->notIn($predicateJSON['lexp']['property'], $predicateJSON['rexp']);
		}
		throw new \RuntimeException('Invalid notIn predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureIsNullPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['exp']['property']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['exp'])
				->isNull($predicateJSON['exp']['property']);
		}
		throw new \RuntimeException('Invalid isNull predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureIsNotNullPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['exp']['property']))
		{
			return $this->getValidPredicateBuilder($predicateBuilder, $predicateJSON['exp'])
				->isNotNull($predicateJSON['exp']['property']);
		}
		throw new \RuntimeException('Invalid isNull predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configurePublishedPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['at']))
		{
			$at = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['at']);
			if ($at === false)
			{
				throw new \RuntimeException('Invalid published at value', 999999);
			}
		}
		else
		{
			$at = null;
		}
		if (isset($predicateJSON['to']))
		{
			$to = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['to']);
			if ($to === false)
			{
				throw new \RuntimeException('Invalid published to value', 999999);
			}
		}
		else
		{
			$to = null;
		}
		return $predicateBuilder->published($at, $to);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNotPublishedPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['at']))
		{
			$at = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['at']);
			if ($at === false)
			{
				throw new \RuntimeException('Invalid notPublished at value', 999999);
			}
		}
		else
		{
			$at = null;
		}
		if (isset($predicateJSON['to']))
		{
			$to = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['to']);
			if ($to === false)
			{
				throw new \RuntimeException('Invalid notPublished to value', 999999);
			}
		}
		else
		{
			$to = null;
		}
		return $predicateBuilder->notPublished($at, $to);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureActivatedPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['at']))
		{
			$at = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['at']);
			if ($at === false)
			{
				throw new \RuntimeException('Invalid activated at value', 999999);
			}
		}
		else
		{
			$at = null;
		}
		if (isset($predicateJSON['to']))
		{
			$to = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['to']);
			if ($to === false)
			{
				throw new \RuntimeException('Invalid activated to value', 999999);
			}
		}
		else
		{
			$to = null;
		}
		return $predicateBuilder->activated($at, $to);
	}


	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNotActivatedPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['at']))
		{
			$at = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['at']);
			if ($at === false)
			{
				throw new \RuntimeException('Invalid notActivated at value', 999999);
			}
		}
		else
		{
			$at = null;
		}
		if (isset($predicateJSON['to']))
		{
			$to = \DateTime::createFromFormat(\DateTime::ISO8601, $predicateJSON['to']);
			if ($to === false)
			{
				throw new \RuntimeException('Invalid notActivated to value', 999999);
			}
		}
		else
		{
			$to = null;
		}
		return $predicateBuilder->notActivated($at, $to);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureChildOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->getTreeManager()->getNodeById($predicateJSON['node']);
			if ($node)
			{
				if (isset($predicateJSON['property']))
				{
					return $predicateBuilder->childOf($node, $predicateJSON['property']);
				}
				return $predicateBuilder->childOf($node);
			}
		}
		throw new \RuntimeException('Invalid childOf predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureDescendantOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->getTreeManager()->getNodeById($predicateJSON['node']);
			if ($node)
			{
				if (isset($predicateJSON['property']))
				{
					return $predicateBuilder->descendantOf($node, $predicateJSON['property']);
				}
				return $predicateBuilder->descendantOf($node);
			}
		}
		throw new \RuntimeException('Invalid descendantOf predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureAncestorOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->getTreeManager()->getNodeById($predicateJSON['node']);
			if ($node)
			{
				if (isset($predicateJSON['property']))
				{
					return $predicateBuilder->ancestorOf($node, $predicateJSON['property']);
				}
				return $predicateBuilder->ancestorOf($node);
			}
		}
		throw new \RuntimeException('Invalid ancestorOf predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNextSiblingOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->getTreeManager()->getNodeById($predicateJSON['node']);
			if ($node)
			{
				if (isset($predicateJSON['property']))
				{
					return $predicateBuilder->nextSiblingOf($node, $predicateJSON['property']);
				}
				return $predicateBuilder->nextSiblingOf($node);
			}
		}
		throw new \RuntimeException('Invalid nextSiblingOf predicate', 999999);
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configurePreviousSiblingOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->getTreeManager()->getNodeById($predicateJSON['node']);
			if ($node)
			{
				if (isset($predicateJSON['property']))
				{
					return $predicateBuilder->previousSiblingOf($node, $predicateJSON['property']);
				}
				return $predicateBuilder->previousSiblingOf($node);
			}
		}
		throw new \RuntimeException('Invalid previousSiblingOf predicate', 999999);
	}
}
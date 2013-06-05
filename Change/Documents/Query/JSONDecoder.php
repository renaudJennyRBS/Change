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
	 * @return \Change\Documents\TreeManager
	 */
	public function getTreeManager()
	{
		return $this->getDocumentServices()->getTreeManager();
	}

	/**
	 * @param string|array $json
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return Query
	 */
	public function getQuery($json)
	{
		$array = is_string($json) ? json_decode($json, true) : $json;
		if (!is_array($array) || !isset($array['model']))
		{
			throw new \InvalidArgumentException('Argument is not a valid json query string', 999999);
		}
		$model = $this->getDocumentServices()->getModelManager()->getModelByName($array['model']);
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: model', 71000);
		}
		$query = new Query($this->getDocumentServices(), $model);
		$this->configureQuery($query, $array);

		return $query;
	}

	/**
	 * @param \Change\Documents\Query\Query $query
	 * @param array $array
	 * @throws \RuntimeException
	 */
	protected function configureQuery($query, $array)
	{
		if (isset($array['where']) && is_array($array['where']))
		{
			foreach($array['where'] as $junction => $predicatesJSON)
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

		if (isset($array['order']) && is_array($array['order']))
		{
			foreach ($array['order'] as $orderInfo)
			{
				if (isset($orderInfo['property']))
				{
					$asc = (!isset($orderInfo['order'])) || $orderInfo['order'] !== 'desc';
					$query->addOrder($orderInfo['property'], $asc);
				}
				else
				{
					throw new \RuntimeException('Invalid Query order', 999999);
				}
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
				throw new \RuntimeException('Invalid predicate op: ' . $predicateJSON['op'], 999999);
			}
		}
		throw new \RuntimeException('Invalid predicate', 999999);
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
			return $predicateBuilder->eq($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->neq($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->gt($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->gte($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->lt($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->lte($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value']);
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
			return $predicateBuilder->like($predicateJSON['lexp']['property'], $predicateJSON['rexp']['value'], $matchMode);
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
			return $predicateBuilder->in($predicateJSON['lexp']['property'], $predicateJSON['rexp']);
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
			return $predicateBuilder->notIn($predicateJSON['lexp']['property'], $predicateJSON['rexp']);
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
			return $predicateBuilder->isNull($predicateJSON['exp']['property']);
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
			return $predicateBuilder->isNotNull($predicateJSON['exp']['property']);
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
		return $predicateBuilder->published();
	}

	/**
	 * @param PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNotPublishedPredicate($predicateBuilder, $predicateJSON)
	{
		return $predicateBuilder->notPublished();
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
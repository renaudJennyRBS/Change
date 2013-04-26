<?php
namespace Change\Http\Rest\Actions;

use Change\Db\Query\Predicates\Like;
use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\Link;
/**
 * @name \Change\Http\Rest\Actions\DocumentQuery
 */
class DocumentQuery
{
	/**
	 * @var \Change\Documents\TreeManager
	 */
	protected $treeManager;

	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$query = $event->getRequest()->getPost()->toArray();
		if (is_array($query) && isset($query['model']))
		{
			$modelName = $query['model'];

			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				throw new \RuntimeException('Invalid Parameter: model', 71000);
			}
			$startIndex = isset($query['offset']) ? intval($query['offset']): 0;
			$maxResults =  isset($query['limit']) ? intval($query['limit']): 10;
			$LCID = isset($query['LCID']) ? $query['LCID'] : null;

			$urlManager = $event->getUrlManager();
			$result = new CollectionResult();
			$selfLink = new Link($urlManager, $event->getRequest()->getPath());
			$result->addLink($selfLink);
			$result->setOffset($startIndex);
			$result->setLimit($maxResults);
			$result->setSort(null);

			$this->treeManager = $event->getDocumentServices()->getTreeManager();

			if ($LCID)
			{
				$event->getDocumentServices()->getDocumentManager()->pushLCID($LCID);
			}

			try
			{
				$queryBuilder = new \Change\Documents\Query\Builder($event->getDocumentServices(), $model);
				$this->configureQuery($queryBuilder, $query);

				$count = $queryBuilder->getCountDocuments();
				$result->setCount($count);
				if ($count && $startIndex < $count)
				{
					$collection = $queryBuilder->getDocuments($startIndex, $maxResults);
					foreach ($collection as $document)
					{
						$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
						$result->addResource($this->addResourceItemInfos($l, $document, $urlManager));
					}
				}

				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$event->setResult($result);

				if ($LCID)
				{
					$event->getDocumentServices()->getDocumentManager()->popLCID();
				}
			}
			catch (\Exception $e)
			{
				if ($LCID)
				{
					$event->getDocumentServices()->getDocumentManager()->popLCID();
				}
				throw $e;
			}
		}
	}

	/**
	 * @param DocumentLink $documentLink
	 * @param AbstractDocument $document
	 * @param UrlManager $urlManager
	 * @return DocumentLink
	 */
	protected function addResourceItemInfos(DocumentLink $documentLink, AbstractDocument $document, UrlManager $urlManager)
	{
		$dm = $document->getDocumentManager();
		if ($documentLink->getLCID())
		{
			$dm->pushLCID($documentLink->getLCID());
		}

		$model = $document->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($document instanceof Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($document instanceof Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}

		if ($document instanceof Localizable)
		{
			$documentLink->setProperty($model->getProperty('refLCID'));
			$documentLink->setProperty($model->getProperty('LCID'));
		}

		if ($model->useCorrection())
		{
			/* @var $cf \Change\Documents\CorrectionFunctions */
			$cf = $document->getCorrectionFunctions();
			if ($cf->hasCorrection())
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$documentLink->setProperty('actions', array($l));
			}
		}

		if ($documentLink->getLCID())
		{
			$dm->popLCID();
		}
		return $documentLink;
	}

	/**
	 * @param \Change\Documents\Query\Builder $queryBuilder
	 * @param array $query
	 * @throws \RuntimeException
	 */
	protected function configureQuery($queryBuilder, $query)
	{
		if (isset($query['where']) && is_array($query['where']))
		{
			foreach($query['where'] as $junction => $predicatesJSON)
			{
				$predicates = $this->configureJunction($queryBuilder->getPredicateBuilder(), $junction, $predicatesJSON, false);
				if ($junction === 'and')
				{
					$queryBuilder->andPredicates($predicates);
				}
				else
				{
					$queryBuilder->orPredicates($predicates);
				}
			}
		}

		if (isset($query['order']) && is_array($query['order']))
		{
			foreach ($query['order'] as $orderInfo)
			{
				if (isset($orderInfo['property']))
				{
					$asc = (!isset($orderInfo['order'])) || $orderInfo['order'] !== 'desc';
					$queryBuilder->addOrder($orderInfo['property'], $asc);
				}
				else
				{
					throw new \RuntimeException('Invalid Query order', 999999);
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configurePublishedPredicate($predicateBuilder, $predicateJSON)
	{
		return $predicateBuilder->published();
	}

	/**
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNotPublishedPredicate($predicateBuilder, $predicateJSON)
	{
		return $predicateBuilder->notPublished();
	}

	/**
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureChildOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->treeManager->getNodeById($predicateJSON['node']);
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureDescendantOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->treeManager->getNodeById($predicateJSON['node']);
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureAncestorOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->treeManager->getNodeById($predicateJSON['node']);
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configureNextSiblingOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->treeManager->getNodeById($predicateJSON['node']);
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param array $predicateJSON
	 * @throws \RuntimeException
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function configurePreviousSiblingOfPredicate($predicateBuilder, $predicateJSON)
	{
		if (isset($predicateJSON['node']) && is_numeric($predicateJSON['node']))
		{
			$node = $this->treeManager->getNodeById($predicateJSON['node']);
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
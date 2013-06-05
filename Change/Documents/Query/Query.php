<?php
namespace Change\Documents\Query;

use Change\Db\DbProvider;
use Change\Db\Query\InterfaceSQLFragment;
use Change\Db\Query\Predicates\InterfacePredicate;
use Change\Db\Query\Predicates\Conjunction;
use Change\Db\Query\Expressions\Parameter;
use Change\Documents\AbstractDocument;
use Change\Documents\AbstractModel;
use Change\Documents\DocumentCollection;
use Change\Documents\DocumentServices;
use Change\Documents\Property;

/**
 * @name \Change\Documents\Query\Query
 */
class Query extends AbstractBuilder
{
	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Db\Query\Builder
	 */
	protected $dbQueryBuilder;

	/**
	 * @var integer
	 */
	protected $aliasCounter = 0;

	/**
	 * @var InterfacePredicate
	 */
	protected $predicate = null;

	/**
	 * @var array
	 */
	protected $parameters;


	/**
	 * @var array
	 */
	protected $orderArray;

	/**
	 * @param DocumentServices $documentServices
	 * @param AbstractModel|string $model
	 * @throws \InvalidArgumentException
	 */
	function __construct(DocumentServices $documentServices, $model)
	{
		$this->setDocumentServices($documentServices);
		if (is_string($model))
		{
			$model = $this->getDocumentServices()->getModelManager()->getModelByName($model);
		}
		if (!($model instanceof AbstractModel))
		{
			throw new \InvalidArgumentException('Argument 2 must by a valid \Change\Documents\AbstractModel', 999999);
		}
		parent::__construct($model);
	}

	/**
	 * @return integer
	 */
	public function getNextAliasCounter()
	{
		return $this->aliasCounter++;
	}

	/**
	 * @return $this
	 */
	public function getQuery()
	{
		return $this;
	}

	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	public function getFragmentBuilder()
	{
		return $this->getDbQueryBuilder()->getFragmentBuilder();
	}

	/**
	 * @param DbProvider $dbProvider
	 */
	public function setDbProvider(DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		$this->setDbProvider($this->documentServices->getApplicationServices()->getDbProvider());
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Db\Query\Builder
	 */
	protected function getDbQueryBuilder()
	{
		if ($this->dbQueryBuilder === null)
		{
			$this->dbQueryBuilder = $this->getDbProvider()->getNewQueryBuilder();
			$this->dbQueryBuilder->select();
		}
		return $this->dbQueryBuilder;
	}

	/**
	 * @param \Change\Db\Query\Builder $qb
	 */
	protected function addDocumentColumns($qb)
	{
		$fb = $qb->getFragmentBuilder();
		$qb->distinct();

		$tableAliasName = $this->getTableAliasName();

		$idColumn = $fb->alias($fb->getDocumentColumn('id', $tableAliasName), 'id');
		$qb->addColumn($idColumn);

		$modelColumn = $fb->alias($fb->getDocumentColumn('model', $tableAliasName), 'model');
		$qb->addColumn($modelColumn);
	}

	/**
	 * @return \Change\Db\Query\Builder
	 */
	public function dbQueryBuilder()
	{
		$dqb = $this->getDbQueryBuilder();
		$this->populateQueryBuilder($dqb);
		$this->setQueryOrders($dqb);
		$this->setQueryParameters($dqb->query());
		return $dqb;
	}

	/**
	 * @return InterfacePredicate|null
	 */
	protected function getPredicate()
	{
		return $this->predicate;
	}

	/**
	 * @param InterfacePredicate $predicate
	 */
	protected function setPredicate(InterfacePredicate $predicate)
	{
		$this->predicate = $predicate;
	}

	/**
	 * @param Parameter $parameter
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	public function setValuedParameter(Parameter $parameter, $value)
	{
		$name = $parameter->getName();
		if (isset($this->parameters[$name]))
		{
			throw new \InvalidArgumentException('Argument 1 must by duplicate parameter name', 999999);
		}
		$this->parameters[$name]= array($parameter, $value);
	}

	/**
	 * @param string|Property $propertyName
	 * @param boolean $asc
	 * @param ChildBuilder $childBuilder
	 * @return $this
	 */
	public function addOrder($propertyName, $asc = true, ChildBuilder $childBuilder = null)
	{
		if ($propertyName instanceof InterfaceSQLFragment)
		{
			$column = $propertyName;
		}
		elseif ($childBuilder !== null)
		{
			$column = $childBuilder->getColumn($propertyName);
		}
		else
		{
			$column = $this->getColumn($propertyName);
		}

		$this->orderArray[] = array($column, $asc);
	}


	/**
	 * @param \Change\Db\Query\Builder $qb
	 * @param \ArrayObject $sysPredicate
	 */
	protected function populateQueryBuilder($qb, \ArrayObject $sysPredicate = null)
	{
		$fb = $qb->getFragmentBuilder();
		$tableAliasName = $this->getTableAliasName();
		$table = $fb->getDocumentTable($this->getModel()->getRootName());
		if ($table->getName() === $tableAliasName)
		{
			$qb->from($table);
		}
		else
		{
			$qb->from($fb->alias($table, $tableAliasName));
		}
		if ($sysPredicate === null)
		{
			$sysPredicate = new \ArrayObject();
		}

		$fromClause = $qb->query()->getFromClause();
		if ($this->hasLocalizedTable())
		{
			$fromClause->addJoin($this->getLocalizedJoin());
		}

		if (is_array($this->joinArray))
		{
			foreach ($this->joinArray as $join)
			{
				/* @var $join \Change\Db\Query\Expressions\Join */
				$fromClause->addJoin($join);
			}
		}

		$models = $this->getModelFilters();
		if (is_array($models))
		{
			if (count($models) == 1)
			{
				$sysPredicate[] = $fb->eq($fb->getDocumentColumn('model', $this->getTableAliasName()), $fb->string($models[0]));
			}
			else
			{
				$sysPredicate[] = $fb->in($fb->getDocumentColumn('model', $this->getTableAliasName()), $models);
			}
		}

		if (is_array($this->childBuilderArray))
		{
			foreach ($this->childBuilderArray as $childBuilder)
			{
				/* @var $childBuilder AbstractBuilder */
				$childBuilder->populateQueryBuilder($qb, $sysPredicate);
			}
		}

		if ($this->predicate !== null)
		{
			$sysPredicate[] = $this->predicate;
		}

		if ($sysPredicate->count())
		{
			$predicate = new Conjunction();
			$predicate->setArguments($sysPredicate->getArrayCopy());
			$qb->where($predicate);
		}
	}
	/**
	 * @param \Change\Db\Query\Builder $qb
	 */
	protected function setQueryOrders($qb)
	{
		if (is_array($this->orderArray) && count($this->orderArray))
		{
			foreach ($this->orderArray as $orderInfo)
			{
				list($expression, $asc ) = $orderInfo;
				if ($asc)
				{
					$qb->orderAsc($expression);
				}
				else
				{
					$qb->orderDesc($expression);
				}
			}
		}
	}

	/**
	 * @param \Change\Db\Query\SelectQuery $query
	 */
	protected function setQueryParameters($query)
	{
		if (is_array($this->parameters))
		{
			foreach ($this->parameters as $pn => $pi)
			{
				if ($query->getParameter($pn) === null)
				{
					$query->addParameter($pi[0]);
				}
				$query->bindParameter($pn, $pi[1]);
			}
		}
	}

	/**
	 * @return AbstractDocument|null
	 */
	public function getFirstDocument()
	{
		$qb = $this->getDbQueryBuilder();
		$this->addDocumentColumns($qb);
		$this->populateQueryBuilder($qb);
		$this->setQueryOrders($qb);
		$sc = $qb->query();
		$this->setQueryParameters($sc);
		$row = $sc->getFirstResult();
		if (is_array($row) && isset($row['model']) && isset($row['id']))
		{
			$model = $this->getDocumentServices()->getModelManager()->getModelByName($row['model']);
			if ($model)
			{
				return $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($row['id'], $model);
			}
		}
		return null;
	}

	/**
	 * @param integer $startIndex
	 * @param integer $maxResults
	 * @return DocumentCollection
	 */
	public function getDocuments($startIndex = 0, $maxResults = null)
	{
		$qb = $this->getDbQueryBuilder();
		$this->addDocumentColumns($qb);
		$this->populateQueryBuilder($qb);
		$this->setQueryOrders($qb);
		$sc = $qb->query();
		$this->setQueryParameters($sc);
		if ($maxResults)
		{
			$sc->setMaxResults($maxResults);
			$sc->setStartIndex($startIndex);
		}
		$collection = new DocumentCollection($this->getDocumentServices()->getDocumentManager(), $sc->getResults());
		return $collection;
	}

	/**
	 * @return integer
	 */
	public function getCountDocuments()
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$qb->select();
		$fb = $qb->getFragmentBuilder();
		$qb->addColumn($fb->alias($fb->func('count', '*'), 'rowCount'));

		$docQueryBuilder =  $this->getDbQueryBuilder();
		$this->addDocumentColumns($docQueryBuilder);
		$this->populateQueryBuilder($docQueryBuilder);
		$qb->from($fb->alias($fb->subQuery($docQueryBuilder->query()), '_tmpCount'));

		$sc = $qb->query();
		$this->setQueryParameters($sc);
		$rows = $sc->getResults();

		if (is_array($rows) && count($rows) === 1)
		{
			return intval($rows[0]['rowCount']);
		}
		else
		{
			return false;
		}
	}
}
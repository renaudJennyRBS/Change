<?php
namespace Change\Documents\Query;

use Change\Db\Query\Expressions\Join;
use Change\Db\DbProvider;
use Change\Documents\AbstractModel;
use Change\Documents\DocumentServices;
use Change\Db\Query\Predicates\InterfacePredicate;
use Change\Db\Query\Expressions\Parameter;
use Change\Documents\Property;

/**
 * @name \Change\Documents\Query\ChildBuilder
 */
class ChildBuilder extends AbstractBuilder
{
	/**
	 * @var AbstractBuilder
	 */
	protected $parent;

	/**
	 * @var integer
	 */
	protected $joinType = Join::INNER_JOIN;

	/**
	 * @var Property
	 */
	protected $parentProperty;

	/**
	 * @var Property
	 */
	protected $property;

	/**
	 * @param AbstractBuilder $parent
	 * @param AbstractModel $model
	 * @param Property $parentProperty
	 * @param Property $property
	 */
	function __construct(AbstractBuilder $parent, AbstractModel $model, Property $parentProperty, Property $property)
	{
		$this->parent = $parent;
		$this->setModel($model);
		$this->setTableAliasName('_jt' . $this->getMaster()->getNextAliasCounter());
		$this->parentProperty = $parentProperty;
		$this->property = $property;
	}

	/**
	 * @api
	 * @return \Change\Documents\Query\AbstractBuilder
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @api
	 * @return Builder
	 */
	public function getMaster()
	{
		return $this->parent->getMaster();
	}

	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	public function getFragmentBuilder()
	{
		return $this->parent->getFragmentBuilder();
	}

	/**
	 * @param \Change\Db\Query\Builder $qb
	 * @param \ArrayObject $sysPredicate
	 */
	protected function populateQueryBuilder($qb, \ArrayObject $sysPredicate = null)
	{
		$this->populateChildJoin($qb);

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

		if (is_array($this->childBuilderArray))
		{
			foreach ($this->childBuilderArray as $childBuilder)
			{
				/* @var $childBuilder AbstractBuilder */
				$childBuilder->populateQueryBuilder($qb, $sysPredicate);
			}
		}
	}

	/**
	 * @param \Change\Db\Query\Builder $qb
	 */
	protected function populateChildJoin($qb)
	{
		$fb = $qb->getFragmentBuilder();
		$fromClause = $qb->query()->getFromClause();
		if ($this->parentProperty->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			$relTableIdentifier = $fb->identifier('_t' . $this->getMaster()->getNextAliasCounter() . 'R');
			$relTable = $fb->getDocumentRelationTable($this->parent->getModel()->getRootName());

			$id = $this->parent->getPredicateBuilder()->eq('id', $fb->getDocumentColumn('id', $relTableIdentifier));
			$relname = $fb->eq($fb->column('relname', $relTableIdentifier), $fb->string($this->parentProperty->getName()));
			$joinCondition = new \Change\Db\Query\Predicates\Conjunction($id, $relname);
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($joinCondition, 'ON');
			$join = new Join($fb->alias($relTable, $relTableIdentifier), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);

			$relatedId = $this->getPredicateBuilder()->eq($this->property, $fb->column('relatedid', $relTableIdentifier));
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($relatedId, 'ON');

			$documentTable = $fb->getDocumentTable($this->model->getRootName());
			$join = new Join($fb->alias($documentTable, $this->getTableAliasName()), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);
		}
		elseif ($this->property->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			$relTableIdentifier = $fb->identifier('_t' . $this->getMaster()->getNextAliasCounter() . 'R');
			$relTable = $fb->getDocumentRelationTable($this->getModel()->getRootName());

			$relatedId = $this->parent->getPredicateBuilder()->eq($this->parentProperty, $fb->column('relatedid', $relTableIdentifier));
			$relname = $fb->eq($fb->column('relname', $relTableIdentifier), $fb->string($this->property->getName()));

			$joinCondition = new \Change\Db\Query\Predicates\Conjunction($relatedId, $relname);
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($joinCondition, 'ON');
			$join = new Join($fb->alias($relTable, $relTableIdentifier), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);


			$id = $this->getPredicateBuilder()->eq('id', $fb->getDocumentColumn('id', $relTableIdentifier));
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($id, 'ON');

			$documentTable = $fb->getDocumentTable($this->model->getRootName());
			$join = new Join($fb->alias($documentTable, $this->getTableAliasName()), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);
		}
		else
		{
			$c1 = $this->parent->getPredicateBuilder()->column($this->parentProperty);
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($this->getPredicateBuilder()->eq($this->property , $c1), 'ON');
			$documentTable = $fb->getDocumentTable($this->model->getRootName());
			$join = new Join($fb->alias($documentTable, $this->getTableAliasName()), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);
		}
	}

	/**
	 * @return InterfacePredicate|null
	 */
	protected function getPredicate()
	{
		return $this->parent->getPredicate();
	}

	/**
	 * @param InterfacePredicate $predicate
	 */
	protected function setPredicate(InterfacePredicate $predicate)
	{
		$this->parent->setPredicate($predicate);
	}

	/**
	 * @param Parameter $parameter
	 * @param mixed $value
	 */
	public function setValuedParameter(Parameter $parameter, $value)
	{
		$this->getMaster()->setValuedParameter($parameter, $value);
	}

	/**
	 * @return DocumentServices
	 */
	protected function getDocumentServices()
	{
		return $this->parent->getDocumentServices();
	}

	/**
	 * @return DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->parent->getDbProvider();
	}
}
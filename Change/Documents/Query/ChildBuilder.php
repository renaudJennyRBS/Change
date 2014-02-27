<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Query;

use Change\Db\Query\Expressions\Join;
use Change\Db\DbProvider;
use Change\Documents\AbstractModel;
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
	 * @param AbstractModel|string $model
	 * @param Property|string $parentProperty
	 * @param Property|string $property
	 * @throws \InvalidArgumentException
	 */
	function __construct(AbstractBuilder $parent, $model, $parentProperty, $property)
	{
		$this->parent = $parent;
		if (is_string($model))
		{
			$model = $this->getModelManager()->getModelByName($model);
		}
		if (!($model instanceof AbstractModel))
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid \Change\Documents\AbstractModel', 999999);
		}
		parent::__construct($model);

		if (is_string($parentProperty))
		{
			$parentProperty = $parent->getValidProperty($parentProperty);
		}
		if (!($parentProperty instanceof Property))
		{
			throw new \InvalidArgumentException('Argument 3 must be a valid \Change\Documents\Property', 999999);
		}
		$this->parentProperty = $parentProperty;

		if (is_string($property))
		{
			$property = $this->getValidProperty($property);
		}
		if (!($property instanceof Property))
		{
			throw new \InvalidArgumentException('Argument 4 must be a valid \Change\Documents\Property', 999999);
		}
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
	 * @return Query
	 */
	public function getQuery()
	{
		return $this->parent->getQuery();
	}

	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	public function getFragmentBuilder()
	{
		return $this->parent->getFragmentBuilder();
	}

	/**
	 * @param string|Property $propertyName
	 * @param boolean $asc
	 * @return $this
	 */
	public function addOrder($propertyName, $asc = true)
	{
		$this->getQuery()->addOrder($propertyName, $asc, $this);
		return $this;
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
			$relTableIdentifier = $fb->identifier('_t' . $this->getQuery()->getNextAliasCounter() . 'R');
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
			$relTableIdentifier = $fb->identifier('_t' . $this->getQuery()->getNextAliasCounter() . 'R');
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
			$c1 = $this->parent->getColumn($this->parentProperty);
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($this->getPredicateBuilder()->eq($this->property , $c1), 'ON');
			$documentTable = $fb->getDocumentTable($this->model->getRootName());
			$join = new Join($fb->alias($documentTable, $this->getTableAliasName()), Join::INNER_JOIN, $joinExpr);
			$fromClause->addJoin($join);
		}
	}

	/**
	 * @return InterfacePredicate|null
	 */
	public function getPredicate()
	{
		return $this->parent->getPredicate();
	}

	/**
	 * @param InterfacePredicate $predicate
	 */
	public function setPredicate(InterfacePredicate $predicate)
	{
		$this->parent->setPredicate($predicate);
	}

	/**
	 * @param Parameter $parameter
	 * @param mixed $value
	 */
	public function setValuedParameter(Parameter $parameter, $value)
	{
		$this->getQuery()->setValuedParameter($parameter, $value);
	}

	public function getDocumentManager()
	{
		return $this->getParent()->getDocumentManager();
	}

	protected function getModelManager()
	{
		return $this->getParent()->getModelManager();
	}

	/**
	 * @return DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->parent->getDbProvider();
	}
}
<?php
namespace Change\Documents\Query;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\ExpressionList;
use Change\Db\Query\Expressions\Subquery;
use Change\Db\Query\InterfaceSQLFragment;
use Change\Db\Query\Predicates\HasPermission;
use Change\Db\Query\Predicates\In;
use Change\Db\Query\Predicates\InterfacePredicate;
use Change\Db\Query\Predicates\Like;
use Change\Db\Query\Predicates\UnaryPredicate;
use Change\Db\Query\SelectQuery;

use Change\Documents\Property;

/**
 * @name \Change\Documents\Query\PredicateBuilder
 */
class PredicateBuilder
{
	/**
	 * @var AbstractBuilder
	 */
	protected $builder;

	/**
	 * @param AbstractBuilder $builder
	 */
	function __construct(AbstractBuilder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * @return \Change\Documents\Query\AbstractBuilder
	 */
	public function getBuilder()
	{
		return $this->builder;
	}

	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	protected function getFragmentBuilder()
	{
		return $this->builder->getFragmentBuilder();
	}

	/**
	 * @param string|Property $propertyName
	 * @throws \InvalidArgumentException
	 * @return array<InterfaceSQLFragment, Property>
	 */
	protected function convertPropertyArgument($propertyName)
	{
		if ($propertyName instanceof InterfaceSQLFragment)
		{
			return array($propertyName, null);
		}
		else
		{
			$lhs = $this->builder->getColumn($propertyName);
			return array($lhs, $this->builder->getValidProperty($propertyName));
		}
	}

	/**
	 * @param \Change\Documents\Property $property
	 * @param mixed $value
	 * @return InterfaceSQLFragment
	 */
	protected function convertValueArgument(Property $property, $value)
	{
		if ($value instanceof InterfaceSQLFragment)
		{
			return $value;
		}
		else
		{
			return $this->builder->getValueAsParameter($value, $property->getType());
		}
	}

	/**
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 * @return array<$lhs, $rhs>
	 */
	protected function convertPropertyValueArgument($propertyName, $value)
	{
		list($lhs, $property) = $this->convertPropertyArgument($propertyName);

		if ($value instanceof InterfaceSQLFragment)
		{
			$rhs = $value;
		}
		elseif ($property instanceof Property)
		{
			$rhs = $this->builder->getValueAsParameter($value, $property->getType());
		}
		else
		{
			$rhs = $this->builder->getValueAsParameter($value);
		}
		return array($lhs, $rhs);
	}

	/**
	 * @param Property|string $propertyName
	 * @return \Change\Db\Query\Expressions\Column
	 */
	public function columnProperty($propertyName)
	{
		if ($propertyName instanceof InterfaceSQLFragment)
		{
			return $propertyName;
		}
		else
		{
			return $this->builder->getColumn($propertyName);
		}
	}

	/**
	 * @api
	 * @param InterfacePredicate|InterfacePredicate[] $predicate1
	 * @param InterfacePredicate $_ [optional]
	 * @throws \InvalidArgumentException
	 * @return InterfacePredicate|\Change\Db\Query\Predicates\Conjunction
	 */
	public function logicAnd($predicate1, $_ = null)
	{
		$args = array();
		foreach (func_get_args() as $idx => $arg)
		{
			if (is_array($arg))
			{
				/* @var $conjunction \Change\Db\Query\Predicates\Conjunction */
				$conjunction = call_user_func_array(array($this, 'logicAnd'), $arg);
				$args = array_merge($args, $conjunction->getArguments());
			}
			elseif ($arg instanceof InterfaceSQLFragment)
			{
				$args[] = $arg;
			}
			else
			{
				throw new \InvalidArgumentException('Argument ' . ($idx + 1) . ' must be a valid InterfaceSQLFragment', 999999);
			}
		}

		return call_user_func_array(array($this->getFragmentBuilder(), 'logicAnd'), $args);
	}

	/**
	 * @api
	 * @param InterfacePredicate|InterfacePredicate[] $predicate1
	 * @param InterfacePredicate $_ [optional]
	 * @throws \InvalidArgumentException
	 * @return InterfacePredicate|\Change\Db\Query\Predicates\Disjunction
	 */
	public function logicOr($predicate1, $_ = null)
	{
		$args = array();
		foreach (func_get_args() as $idx => $arg)
		{
			if (is_array($arg))
			{
				/* @var $disjunction \Change\Db\Query\Predicates\Disjunction */
				$disjunction = call_user_func_array(array($this, 'logicOr'), $arg);
				$args = array_merge($args, $disjunction->getArguments());
			}
			elseif ($arg instanceof InterfaceSQLFragment)
			{
				$args[] = $arg;
			}
			else
			{
				throw new \InvalidArgumentException('Argument ' . ($idx + 1) . ' must be a valid InterfaceSQLFragment', 999999);
			}
		}

		return call_user_func_array(array($this->getFragmentBuilder(), 'logicOr'), $args);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function eq($propertyName, $value)
	{
		$fb = $this->getFragmentBuilder();
		$property = $this->builder->getValidProperty($propertyName);
		if ($property && $property->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			$abstractBuilder = $this->builder;
			$sq = new \Change\Db\Query\SelectQuery();
			$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());
			$fromClause = new \Change\Db\Query\Clauses\FromClause();
			$fromTable = $fb->getDocumentRelationTable($abstractBuilder->getModel()->getRootName());
			$fromClause->setTableExpression($fromTable);
			$sq->setFromClause($fromClause);

			$docEq = $fb->eq($fb->getDocumentColumn('id', $fromTable), $abstractBuilder->getColumn('id'));
			$relnamePredicate = $fb->eq($fb->column('relname', $fromTable), $fb->string($property->getName()));
			$idPredicate = $fb->eq($fb->column('relatedid', $fromTable),
				$abstractBuilder->getValueAsParameter($value, Property::TYPE_INTEGER));

			$and = new \Change\Db\Query\Predicates\Conjunction($docEq, $relnamePredicate, $idPredicate);
			$where = new \Change\Db\Query\Clauses\WhereClause($and);
			$sq->setWhereClause($where);
			return new \Change\Db\Query\Predicates\Exists(new \Change\Db\Query\Expressions\SubQuery($sq));
		}
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $fb->eq($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function neq($propertyName, $value)
	{
		$fb = $this->getFragmentBuilder();
		$property = $this->builder->getValidProperty($propertyName);
		if ($property && $property->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			$abstractBuilder = $this->builder;
			$sq = new \Change\Db\Query\SelectQuery();
			$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());
			$fromClause = new \Change\Db\Query\Clauses\FromClause();
			$fromTable = $fb->getDocumentRelationTable($abstractBuilder->getModel()->getRootName());
			$fromClause->setTableExpression($fromTable);
			$sq->setFromClause($fromClause);

			$docEq = $fb->eq($fb->getDocumentColumn('id', $fromTable), $abstractBuilder->getColumn('id'));
			$relnamePredicate = $fb->eq($fb->column('relname', $fromTable), $fb->string($property->getName()));
			$idPredicate = $fb->eq($fb->column('relatedid', $fromTable),
				$abstractBuilder->getValueAsParameter($value, Property::TYPE_INTEGER));

			$and = new \Change\Db\Query\Predicates\Conjunction($docEq, $relnamePredicate, $idPredicate);
			$where = new \Change\Db\Query\Clauses\WhereClause($and);
			$sq->setWhereClause($where);
			return new \Change\Db\Query\Predicates\Exists(new \Change\Db\Query\Expressions\SubQuery($sq), true);
		}
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $fb->neq($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function gt($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->gt($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function lt($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->lt($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function gte($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->gte($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function lte($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->lte($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @param integer $matchMode
	 * @param boolean $caseSensitive
	 * @return InterfacePredicate
	 */
	public function like($propertyName, $value, $matchMode = Like::ANYWHERE, $caseSensitive = false)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->like($lhs, $rhs, $matchMode, $caseSensitive);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param string|array|\Change\Db\Query\Expressions\AbstractExpression $rhs1
	 * @return InterfacePredicate
	 */
	public function in($propertyName, $rhs1)
	{
		list($lhs, $property) = $this->convertPropertyArgument($propertyName);
		if ($rhs1 instanceof SelectQuery)
		{
			$rhs = $this->getFragmentBuilder()->subQuery($rhs1);
		}
		elseif ($rhs1 instanceof Subquery || $rhs1 instanceof ExpressionList)
		{
			$rhs = $rhs1;
		}
		else
		{
			$rhs = new ExpressionList();
			$arguments = func_get_args();
			array_shift($arguments);
			if (count($arguments))
			{
				$builder = $this->builder;
				$converter = function ($item) use ($builder, $property)
				{
					return ($item instanceof InterfaceSQLFragment) ? $item : $builder->getValueAsParameter($item, $property);
				};

				foreach ($arguments as $argument)
				{
					if (is_array($argument))
					{
						foreach ($argument as $subArgument)
						{
							$rhs->add($converter($subArgument));
						}
					}
					else
					{
						$rhs->add($converter($argument));
					}
				}
			}
		}
		return new In($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param string | \Change\Db\Query\Expressions\AbstractExpression $rhs
	 * @return InterfacePredicate
	 */
	public function notIn($propertyName, $rhs)
	{
		$pre = call_user_func_array(array($this, 'in'), func_get_args());
		$pre->setNot(true);
		return $pre;
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @return InterfacePredicate
	 */
	public function isNull($propertyName)
	{
		list($expression, $property) = $this->convertPropertyArgument($propertyName);

		/* @var $property Property */
		if ($property !== null
			&& in_array($property->getType(),
				array(Property::TYPE_DOCUMENTARRAY, Property::TYPE_DOCUMENT, Property::TYPE_DOCUMENTID))
		)
		{
			return $this->getFragmentBuilder()->eq($expression, $this->builder->getValueAsParameter(0, Property::TYPE_INTEGER));
		}
		return new UnaryPredicate($expression, UnaryPredicate::ISNULL);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @return InterfacePredicate
	 */
	public function isNotNull($propertyName)
	{
		list($expression, $property) = $this->convertPropertyArgument($propertyName);

		/* @var $property Property */
		if ($property !== null
			&& in_array($property->getType(),
				array(Property::TYPE_DOCUMENTARRAY, Property::TYPE_DOCUMENT, Property::TYPE_DOCUMENTID))
		)
		{
			return $this->getFragmentBuilder()->neq($expression, $this->builder->getValueAsParameter(0, Property::TYPE_INTEGER));
		}
		return new UnaryPredicate($expression, UnaryPredicate::ISNOTNULL);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param \Change\Documents\AbstractModel|string $model
	 * @param \Change\Documents\Property|string $modelProperty
	 * @param boolean $notExist
	 * @throws \InvalidArgumentException
	 * @return InterfacePredicate
	 */
	protected function buildExists($propertyName, $model, $modelProperty, $notExist)
	{
		list($expression, $property) = $this->convertPropertyArgument($propertyName);

		/* @var $property Property */
		if ($property !== null && $model !== null && $modelProperty !== null)
		{
			$fragmentBuilder = $this->getFragmentBuilder();

			if (!$model instanceof \Change\Documents\AbstractModel)
			{
				$model = $this->getBuilder()->getDocumentManager()->getModelManager()->getModelByName($model);
			}

			if ($model)
			{
				$modelPropertyName = $modelProperty instanceof \Change\Documents\Property ? $modelProperty->getName() : $modelProperty;

				$modelProperty = $model->getProperty($modelPropertyName);

				if ($modelProperty )
				{

					if ($modelProperty->getLocalized())
					{
						$fromTable = $fragmentBuilder->getDocumentI18nTable($model->getRootName());
						$eq = $fragmentBuilder->eq($expression, $fragmentBuilder->getDocumentColumn($modelPropertyName, $fromTable));
					}
					elseif ($modelProperty->getType() == \Change\Documents\Property::TYPE_DOCUMENTARRAY)
					{
						$fromTable = $fragmentBuilder->getDocumentRelationTable($model->getRootName());
						$id = $fragmentBuilder->eq($expression, $fragmentBuilder->getDocumentColumn('relatedid', $fromTable));
						$rel = $fragmentBuilder->eq($fragmentBuilder->string($modelPropertyName), $fragmentBuilder->getDocumentColumn('relname', $fromTable));
						$eq = $fragmentBuilder->logicAnd($id, $rel);
					}
					else
					{
						$fromTable = $fragmentBuilder->getDocumentTable($model->getRootName());
						$eq = $fragmentBuilder->eq($expression, $fragmentBuilder->getDocumentColumn($modelPropertyName, $fromTable));
					}

					$sq = new \Change\Db\Query\SelectQuery();
					$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());
					$fromClause = new \Change\Db\Query\Clauses\FromClause();
					$fromClause->setTableExpression($fromTable);
					$sq->setFromClause($fromClause);

					$where = new \Change\Db\Query\Clauses\WhereClause($eq);
					$sq->setWhereClause($where);

					if ($notExist)
					{
						return $fragmentBuilder->notExists(new \Change\Db\Query\Expressions\SubQuery($sq));
					}
					else
					{
						return $fragmentBuilder->exists(new \Change\Db\Query\Expressions\SubQuery($sq));
					}
				}
			}
		}
		throw new \InvalidArgumentException('Invalid exists predicate arguments', 999999);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param \Change\Documents\AbstractModel|string $model
	 * @param \Change\Documents\Property|string $modelProperty
	 * @throws \InvalidArgumentException
	 * @return InterfacePredicate
	 */
	public function exists($propertyName, $model, $modelProperty)
	{
		return $this->buildExists($propertyName, $model, $modelProperty, false);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param \Change\Documents\AbstractModel|string $model
	 * @param \Change\Documents\Property|string $modelProperty
	 * @throws \InvalidArgumentException
	 * @return InterfacePredicate
	 */
	public function notExists($propertyName, $model, $modelProperty)
	{
		return $this->buildExists($propertyName, $model, $modelProperty, true);
	}

	/**
	 * @api
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function published($at = null, $to = null)
	{
		if (!$this->builder->getModel()->isPublishable())
		{
			throw new \RuntimeException('Model is not publishable: ' . $this->builder->getModel(), 999999);
		}
		$fb = $this->getFragmentBuilder();

		if (!($at instanceof \DateTime))
		{
			$at = new \DateTime();
		}
		if (!($to instanceof \DateTime))
		{
			$to = $at;
		}

		return $fb->logicAnd(
			$this->eq('publicationStatus', \Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE),
			$fb->logicOr($this->isNull('startPublication'), $this->lte('startPublication', $at)),
			$fb->logicOr($this->isNull('endPublication'), $this->gt('endPublication', $to))
		);
	}

	/**
	 * @api
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function notPublished($at = null, $to = null)
	{
		if (!$this->builder->getModel()->isPublishable())
		{
			throw new \RuntimeException('Model is not publishable: ' . $this->builder->getModel(), 999999);
		}
		$fb = $this->getFragmentBuilder();
		if (!($at instanceof \DateTime))
		{
			$at = new \DateTime();
		}
		if (!($to instanceof \DateTime))
		{
			$to = $at;
		}

		return $fb->logicOr(
			$this->neq('publicationStatus', \Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE),
			$fb->logicAnd($this->isNotNull('startPublication'), $this->gt('startPublication', $at)),
			$fb->logicAnd($this->isNotNull('endPublication'), $this->lte('endPublication', $to))
		);
	}

	/**
	 * @api
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function activated($at = null, $to = null)
	{
		if (!$this->builder->getModel()->isActivable())
		{
			throw new \RuntimeException('Model is not activable: ' . $this->builder->getModel(), 999999);
		}
		$fb = $this->getFragmentBuilder();

		if (!($at instanceof \DateTime))
		{
			$at = new \DateTime();
		}
		if (!($to instanceof \DateTime))
		{
			$to = $at;
		}

		return $fb->logicAnd(
			$this->eq('active', true),
			$fb->logicOr($this->isNull('startActivation'), $this->lte('startActivation', $at)),
			$fb->logicOr($this->isNull('endActivation'), $this->gt('endActivation', $to))
		);
	}

	/**
	 * @api
	 * @param AbstractExpression|\Change\User\UserInterface|integer|null $accessor
	 * @param AbstractExpression|string|null $role
	 * @param AbstractExpression|integer|null $resource
	 * @param AbstractExpression|string|null $privilege
	 * @return HasPermission
	 */
	public function hasPermission($accessor = null, $role = null, $resource = null, $privilege = null)
	{
		if ($resource === null)
		{
			$resource = $this->builder->getColumn('id');
		}
		return $this->getFragmentBuilder()->hasPermission($accessor, $role, $resource, $privilege);
	}

	/**
	 * @api
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function notActivated($at = null, $to = null)
	{
		if (!$this->builder->getModel()->isActivable())
		{
			throw new \RuntimeException('Model is not activable: ' . $this->builder->getModel(), 999999);
		}
		$fb = $this->getFragmentBuilder();
		if (!($at instanceof \DateTime))
		{
			$at = new \DateTime();
		}
		if (!($to instanceof \DateTime))
		{
			$to = $at;
		}

		return $fb->logicOr(
			$this->neq('active', true),
			$fb->logicAnd($this->isNotNull('startActivation'), $this->gt('startActivation', $at)),
			$fb->logicAnd($this->isNotNull('endActivation'), $this->lte('endActivation', $to))
		);
	}
}
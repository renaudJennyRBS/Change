<?php
namespace Change\Documents\Query;

use Change\Db\Query\Predicates\InterfacePredicate;
use Change\Db\Query\Expressions\ExpressionList;
use Change\Db\Query\Expressions\Subquery;
use Change\Db\Query\InterfaceSQLFragment;
use Change\Db\Query\Predicates\In;
use Change\Db\Query\Predicates\Like;
use Change\Db\Query\Predicates\UnaryPredicate;
use Change\Db\Query\SQLFragmentBuilder;
use Change\Db\Query\SelectQuery;
use Change\Documents\Property;

use Change\Documents\AbstractDocument;
use Change\Documents\TreeNode;
use Change\Db\Query\Expressions\Join;

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
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function eq($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->eq($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function neq($propertyName, $value)
	{
		list($lhs, $rhs) = $this->convertPropertyValueArgument($propertyName, $value);
		return $this->getFragmentBuilder()->neq($lhs, $rhs);
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
		if ($property !== null && $property->getType() === Property::TYPE_DOCUMENTARRAY)
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
		if ($property !== null && $property->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			return $this->getFragmentBuilder()->neq($expression, $this->builder->getValueAsParameter(0, Property::TYPE_INTEGER));
		}
		return new UnaryPredicate($expression, UnaryPredicate::ISNOTNULL);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function published()
	{
		if (!$this->builder->getModel()->isPublishable())
		{
			throw new \RuntimeException('Model is not publishable: ' . $this->builder->getModel(), 999999);
		}
		$fb = $this->getFragmentBuilder();
		$publicationDate = new \DateTime();

		return $fb->logicAnd(
			$this->eq('publicationStatus', \Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE),
			$fb->logicOr($this->isNull('startPublication'), $this->lte('startPublication', $publicationDate)),
			$fb->logicOr($this->isNull('endPublication'), $this->gt('endPublication', $publicationDate))
		);
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function validateNodeArgument($node)
	{
		if ($node instanceof AbstractDocument)
		{
			$document = $node;
		}
		elseif ($node instanceof TreeNode)
		{
			$document = $node->getDocument();
		}
		elseif (is_numeric($node))
		{
			$document = $this->builder->getMaster()->getDocumentServices()->getDocumentManager()->getDocumentInstance($node);
		}
		else
		{
			$document = null;
		}

		if ($document === null || !$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Argument 1 must by a valid node', 999999);
		}

		$node = $this->builder->getMaster()->getDocumentServices()->getTreeManager()->getNodeByDocument($document);
		if ($node === null)
		{
			throw new \InvalidArgumentException('Argument 1 must by a valid node', 999999);
		}
		return array($document, $node);
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function childOf($node, $propertyName = 'id')
	{
		list($document, $node) = $this->validateNodeArgument($node);

		/* @var $document AbstractDocument */
		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getMaster()->getNextAliasCounter() . 'T';

		$id =  $this->eq($propertyName, $fb->getDocumentColumn('id', $treeTableIdentifier));
		$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($id, 'ON');

		$join = new Join($fb->alias($treeTable, $treeTableIdentifier), Join::INNER_JOIN, $joinExpr);
		$this->builder->addJoin($treeTableIdentifier, $join);

		return $fb->eq($fb->column('parent_id', $treeTableIdentifier), $this->builder->getValueAsParameter($document->getId(), Property::TYPE_INTEGER));
	}
}
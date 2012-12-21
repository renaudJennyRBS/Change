<?php
namespace Change\Db\Query;

use Change\Db\Query\Expressions\Assignment;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\AbstractOperation;
use Change\Db\Query\Expressions\Func;
use Change\Db\Query\Expressions\Table;
use Change\Db\Query\Expressions\Identifier;
use Change\Db\Query\Expressions\Column;
use Change\Db\Query\Expressions\Alias;
use Change\Db\Query\Expressions\Parameter;
use Change\Db\Query\Expressions\Numeric;
use Change\Db\Query\Expressions\String;
use Change\Db\Query\Expressions\ExpressionList;
use Change\Db\Query\Expressions\SubQuery;
use Change\Db\Query\Expressions\Raw;
use Change\Db\Query\Predicates\UnaryPredicate;
use Change\Db\Query\Predicates\BinaryPredicate;
use Change\Db\Query\Predicates\Like;

/**
 * @name \Change\Db\Query\SQLFragmentBuilder
 */
class SQLFragmentBuilder
{	
	/**
	 * @api
	 * @param string $name
	 * @param array $args
	 * @return \Change\Db\Query\Expressions\Func
	 */
	public function func($name)
	{
		$funcArgs = func_get_args();
		array_shift($funcArgs);
		return new Func($name, $this->normalizeValue($funcArgs));
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\Func
	 */
	public function sum()
	{
		return new Func('SUM', $this->normalizeValue(func_get_args()));
	}
	
	
	/**
	 * Build a reference to a given table
	 * 
	 * @api
	 * @param string $tableName
	 * @param string $dbName
	 * @return \Change\Db\Query\Expressions\Table
	 */
	public function table($tableName, $dbName = null)
	{
		return new Table($tableName, $dbName);
	}
	
	/**
	 * Build an identifier string (eg: `test` on MySQL) which can be passed
	 * for instance as the second argument of the alias method.
	 *
	 * @api
	 * @param string $tableName
	 * @param string $dbName
	 */
	public function identifier()
	{
		return new Identifier(func_get_args());
	}
	
	/**
	 * @api
	 * @param string $name
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier | string $tableOrIdentifier
	 * @return \Change\Db\Query\Expressions\Column
	 */
	public function column($name, $tableOrIdentifier = null)
	{
		if (is_string($name))
		{
			$name = $this->identifier($name);
		}
		
		if (is_string($tableOrIdentifier))
		{
			$tableOrIdentifier = $this->identifier($tableOrIdentifier);
		}
		return new Column($name, $tableOrIdentifier);
	}
	
	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @param \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs (if string assume identifier)
	 * @return \Change\Db\Query\Expressions\Alias
	 */
	public function alias(AbstractExpression $lhs, $rhs)
	{
		if (is_string($rhs))
		{
			$rhs = $this->identifier($rhs);
		}
		if (!($rhs instanceof AbstractExpression))
		{
			throw new \InvalidArgumentException('Could not convert argument 2 to an Expression');
		}
		return new Alias($lhs, $rhs);
	}

	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @param string | \Change\Db\Query\AbstractExpression $lhs (if string assume identifier)
	 * @param string | \Change\Db\Query\AbstractExpression $rhs (if string assume string)
	 * @return \Change\Db\Query\Expressions\Assignment
	 */
	public function assignment($lhs, $rhs)
	{
		if (is_string($lhs))
		{
			$lhs = $this->identifier($lhs);
		}
		if (!($lhs instanceof AbstractExpression))
		{
			throw new \InvalidArgumentException('Could not convert argument 1 to an Expression');
		}
		
		if (is_string($rhs))
		{
			$rhs = $this->string($rhs);
		}
		if (!($rhs instanceof AbstractExpression))
		{
			throw new \InvalidArgumentException('Could not convert argument 2 to an Expression');
		}
		
		return new Assignment($lhs, $rhs);
	}

	/**
	 * @api
	 * @param string $parameterName
	 * @param \Change\Db\Query\AbstractQuery|\Change\Db\Query\Builder $queryOrBuilder
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function parameter($parameterName, $queryOrBuilder = null)
	{
		$p = new Parameter($parameterName, Parameter::STRING);
		if ($queryOrBuilder !== null)
		{
			$this->bindParameter($p, $queryOrBuilder);
		}
		return $p;
	}
	
	/**
	 * @api
	 * @param string $parameterName
	 * @param \Change\Db\Query\AbstractQuery|\Change\Db\Query\Builder $queryOrBuilder
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function numericParameter($parameterName, $queryOrBuilder = null)
	{
		$p = new Parameter($parameterName, Parameter::NUMERIC);
		if ($queryOrBuilder !== null)
		{
			$this->bindParameter($p, $queryOrBuilder);
		}
		return $p;
	}
	
	/**
	 * @api
	 * @param string $parameterName
	 * @param \Change\Db\Query\AbstractQuery|\Change\Db\Query\Builder $queryOrBuilder
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function dateTimeparameter($parameterName, $queryOrBuilder = null)
	{
		$p = new Parameter($parameterName, Parameter::DATETIME);
		if ($queryOrBuilder !== null)
		{
			$this->bindParameter($p, $queryOrBuilder);
		}
		return $p;
	}
	
	/**
	 * @api
	 * @param string $parameterName
	 * @param \Change\Db\Query\AbstractQuery|\Change\Db\Query\Builder $queryOrBuilder
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function lobParameter($parameterName, $queryOrBuilder = null)
	{
		$p = new Parameter($parameterName, Parameter::LOB);
		if ($queryOrBuilder !== null)
		{
			$this->bindParameter($p, $queryOrBuilder);
		}
		return $p;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Parameter $parameter
	 * @param mixed $queryOrBuilder
	 */
	protected function bindParameter($parameter, $queryOrBuilder)
	{
		if ($queryOrBuilder instanceof \Change\Db\Query\AbstractQuery
			|| $queryOrBuilder instanceof \Change\Db\Query\Builder
			|| $queryOrBuilder instanceof \Change\Db\Query\StatmentBuilder)
		{
			$queryOrBuilder->addParameter($parameter);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid Query or Builder');
		}
	}
	
	/**
	 * @api
	 * @param numeric $number
	 * @return \Change\Db\Query\Expressions\Numeric
	 */
	public function number($number)
	{
		return new Numeric($number);
	}
	
	/**
	 * @api
	 * @param string $string
	 * @return \Change\Db\Query\Expressions\String
	 */
	public function string($string)
	{
		return new String($string);
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function expressionList()
	{
		return new ExpressionList($this->normalizeValue(func_get_args()));
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return \Change\Db\Query\Expressions\Subquery
	 */
	public function subQuery(\Change\Db\Query\SelectQuery $selectQuery)
	{
		return new SubQuery($selectQuery);
	}
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function eq($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::EQUAL);
	}
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function neq($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::NOTEQUAL);
	}	
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function gt($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::GREATERTHAN);
	}
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function gte($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::GREATERTHANOREQUAL);
	}
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function lt($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::LESSTHAN);
	}
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 */
	public function lte($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new BinaryPredicate($lhs, $rhs, BinaryPredicate::LESSTHANOREQUAL);
	}
	
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\Like
	 */
	public function like($lhs, $rhs, $matchMode = Like::ANYWHERE, $caseSensitive = false)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new Like($lhs, $rhs, $matchMode, ($caseSensitive == true));
	}
	
	
	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $expression
	 * @return \Change\Db\Query\Predicates\UnaryPredicate
	 */
	public function isNull($expression)
	{
		$expression = $this->normalizeValue($expression);
		return new UnaryPredicate($expression, UnaryPredicate::ISNULL);
	}

	/**
	 * @api
	 * @param string | \Change\Db\Query\AbstractExpression $expression
	 * @return \Change\Db\Query\Predicates\UnaryPredicate
	 */
	public function isNotNull($expression)
	{
		$expression = $this->normalizeValue($expression);
		return new UnaryPredicate($expression, UnaryPredicate::ISNOTNULL);
	}
		
	/**
	 * @api
	 * @return \Change\Db\Query\Predicates\Conjunction
	 */
	public function logicAnd()
	{
		$result = new \Change\Db\Query\Predicates\Conjunction();
		$result->setArguments($this->normalizeValue(func_get_args()));
		return $result;
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Predicates\Disjunction
	 */
	public function logicOr()
	{
		$result = new \Change\Db\Query\Predicates\Disjunction();
		$result->setArguments($this->normalizeValue(func_get_args()));
		return $result;
	}
	
	/**
	 * For internal use only.
	 *
	 * @param  \Change\Db\Query\AbstractExpression $object
	 * @return \Change\Db\Query\Expressions\Raw|\Change\Db\Query\Expressions\AbstractExpression
	 */
	public function normalizeValue($object, $converter = null)
	{
		if ($converter == null)
		{
			$converter = function ($item) {
				return new Raw(strval($item));
			};
		}
		if (is_array($object))
		{
			$builder = $this;
			return array_map(function ($item) use($builder, $converter) {
				return $builder->normalizeValue($item, $converter);
			}, $object);
		}
		if (!($object instanceof AbstractExpression))
		{
			return call_user_func($converter, $object);
		}
		return $object;
	}
}
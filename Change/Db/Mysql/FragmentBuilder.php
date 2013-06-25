<?php
namespace Change\Db\Mysql;

use Change\Db\Query;
use Change\Db\ScalarType;

/**
* @name \Change\Db\Mysql\FragmentBuilder
*/
class FragmentBuilder
{
	/**
	 * @var DbProvider
	 */
	protected $dbProvider;


	function __construct($dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @api
	 * @param Query\InterfaceSQLFragment $fragment
	 * @throws \RuntimeException
	 * @return string
	 */
	public function buildSQLFragment(Query\InterfaceSQLFragment $fragment)
	{
		if ($fragment instanceof Query\Expressions\Table)
		{
			$identifierParts = array();
			$dbName = $fragment->getDatabase();
			$tableName = $fragment->getName();
			if (!empty($dbName))
			{
				$identifierParts[] = '`' . $dbName . '`';
			}
			$identifierParts[] = '`' . $tableName . '`';
			return implode('.', $identifierParts);
		}
		elseif ($fragment instanceof Query\Expressions\Column)
		{
			$columnName = $this->buildSQLFragment($fragment->getColumnName());
			$tableOrIdentifier = $fragment->getTableOrIdentifier();
			$table = ($tableOrIdentifier) ? $this->buildSQLFragment($tableOrIdentifier) : null;
			return empty($table) ? $columnName : $table . '.' . $columnName;
		}
		elseif ($fragment instanceof Query\Expressions\Parentheses)
		{
			return '(' . $this->buildSQLFragment($fragment->getExpression()) . ')';
		}
		elseif ($fragment instanceof Query\Expressions\Identifier)
		{
			return implode('.', array_map(function ($part)
			{
				return '`' . $part . '`';
			}, $fragment->getParts()));
		}
		elseif ($fragment instanceof Query\Expressions\Concat)
		{
			return 'CONCAT(' . implode(', ', $this->buildSQLFragmentArray($fragment->getList())) . ')';
		}
		elseif ($fragment instanceof Query\Expressions\ExpressionList)
		{
			return implode(', ', $this->buildSQLFragmentArray($fragment->getList()));
		}
		elseif ($fragment instanceof Query\Expressions\AllColumns)
		{
			return $fragment->toSQL92String();
		}
		elseif ($fragment instanceof Query\Expressions\Raw)
		{
			return $fragment->toSQL92String();
		}
		elseif ($fragment instanceof Query\Predicates\Conjunction)
		{
			return '(' . implode(' AND ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';
		}
		elseif ($fragment instanceof Query\Predicates\Disjunction)
		{
			return '(' . implode(' OR ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';
		}
		elseif ($fragment instanceof Query\Predicates\Like)
		{
			$fragment->checkCompile();
			$rhe = $fragment->getCompletedRightHandExpression();
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($rhe);
		}
		elseif ($fragment instanceof Query\Predicates\In)
		{
			$fragment->checkCompile();
			$rhe = $fragment->getCompletedRightHandExpression();
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($rhe);
		}
		elseif ($fragment instanceof Query\Predicates\Exists)
		{
			$fragment->checkCompile();
			return $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getExpression());
		}
		elseif ($fragment instanceof Query\Expressions\BinaryOperation)
		{
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getRightHandExpression());
		}
		elseif ($fragment instanceof Query\Predicates\UnaryPredicate)
		{
			return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();
		}
		elseif ($fragment instanceof Query\Expressions\OrderingSpecification)
		{
			return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();
		}
		elseif ($fragment instanceof Query\Expressions\UnaryOperation)
		{
			return $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getExpression());
		}
		elseif ($fragment instanceof Query\Expressions\Join)
		{
			$joinedTable = $fragment->getTableExpression();
			if (!$joinedTable)
			{
				throw new \RuntimeException('A joined table is required', 42002);
			}
			$parts = array();
			if ($fragment->isNatural())
			{
				$parts[] = 'NATURAL';
			}
			if ($fragment->isQualified())
			{
				switch ($fragment->getType())
				{
					case Query\Expressions\Join::LEFT_OUTER_JOIN :
						$parts[] = 'LEFT OUTER JOIN';
						break;
					case Query\Expressions\Join::RIGHT_OUTER_JOIN :
						$parts[] = 'RIGHT OUTER JOIN';
						break;
					case Query\Expressions\Join::FULL_OUTER_JOIN :
						$parts[] = 'FULL OUTER JOIN';
						break;
					case Query\Expressions\Join::INNER_JOIN :
					default :
						$parts[] = 'INNER JOIN';
						break;
				}
			}
			else
			{
				$parts[] = 'CROSS JOIN';
			}
			$parts[] = $this->buildSQLFragment($joinedTable);
			if (!$fragment->isNatural())
			{
				$joinSpecification = $fragment->getSpecification();
				$parts[] = $this->buildSQLFragment($joinSpecification);
			}
			return implode(' ', $parts);
		}
		elseif ($fragment instanceof Query\Expressions\Value)
		{
			$v = $fragment->getValue();
			if ($v === null)
			{
				return 'NULL';
			}
			switch ($fragment->getScalarType())
			{
				case ScalarType::BOOLEAN :
					return ($v) ? '1' : '0';
				case ScalarType::INTEGER :
					return strval(intval($v));
				case ScalarType::DECIMAL :
					return strval(floatval($v));
				case ScalarType::DATETIME :
					if ($v instanceof \DateTime)
					{
						$v->setTimezone(new \DateTimeZone('UTC'));
						$v = $v->format('Y-m-d H:i:s');
					}
			}
			return $this->dbProvider->getDriver()->quote($v);
		}
		elseif ($fragment instanceof Query\Expressions\Parameter)
		{
			return ':' . $fragment->getName();
		}
		elseif ($fragment instanceof Query\Expressions\SubQuery)
		{
			return '(' . $this->buildQuery($fragment->getSubQuery()) . ')';
		}
		elseif ($fragment instanceof Query\Expressions\Func)
		{
			return $this->buildSQLFunc($fragment);
		}
		elseif ($fragment instanceof Query\AbstractQuery)
		{
			return $this->buildQuery($fragment);
		}
		elseif ($fragment instanceof Query\Clauses\AbstractClause)
		{
			return $this->buildAbstractClause($fragment);
		}
		return $this->dbProvider->buildCustomSQLFragment($fragment);
	}

	/**
	 * @param Query\InterfaceSQLFragment[]|Query\Expressions\AbstractExpression[] $fragments
	 * @return string[]
	 */
	protected function buildSQLFragmentArray($fragments)
	{
		$strings = array();
		foreach ($fragments as $fragment)
		{
			$strings[] = $this->buildSQLFragment($fragment);
		}
		return $strings;
	}

	/**
	 * @param Query\Expressions\Func $func
	 * @return string
	 */
	protected function buildSQLFunc($func)
	{
		return $func->getFunctionName() . '(' .implode(',', $this->buildSQLFragmentArray($func->getArguments())). ')';
	}

	/**
	 * @param Query\AbstractQuery $query
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function buildQuery(Query\AbstractQuery $query)
	{
		if ($query instanceof Query\SelectQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getSelectClause()));
			$fromClause = $query->getFromClause();
			if ($fromClause)
			{
				$parts[] = $this->buildAbstractClause($fromClause);
			}
			$whereClause = $query->getWhereClause();
			if ($whereClause)
			{
				$parts[] = $this->buildAbstractClause($whereClause);
			}

			$groupByClause = $query->getGroupByClause();
			if ($groupByClause)
			{
				$parts[] = $this->buildAbstractClause($groupByClause);
			}

			$havingClause = $query->getHavingClause();
			if ($havingClause)
			{
				$parts[] = $this->buildAbstractClause($havingClause);
			}

			$orderByClause = $query->getOrderByClause();
			if ($orderByClause)
			{
				$parts[] = $this->buildAbstractClause($orderByClause);
			}

			if ($query->getMaxResults())
			{

				$parts[] = 'LIMIT';
				if ($query->getStartIndex())
				{
					$parts[] = strval(max(0, $query->getStartIndex())) . ',';
				}
				$parts[] = strval(max(1, $query->getMaxResults()));
			}

			return implode(' ', $parts);
		}
		elseif ($query instanceof Query\InsertQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getInsertClause()));
			if ($query->getValuesClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getValuesClause());
			}
			elseif ($query->getSelectQuery() !== null)
			{
				$parts[] = $this->buildQuery($query->getSelectQuery());
			}
			return implode(' ', $parts);
		}
		elseif ($query instanceof Query\UpdateQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getUpdateClause()),
				$this->buildAbstractClause($query->getSetClause()));
			if ($query->getWhereClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getWhereClause());
			}
			return implode(' ', $parts);
		}
		elseif ($query instanceof Query\DeleteQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getDeleteClause()),
				$this->buildAbstractClause($query->getFromClause()));
			if ($query->getWhereClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getWhereClause());
			}
			return implode(' ', $parts);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 must be a Select, Insert, Update or Delete query', 999999);
		}
	}

	/**
	 * @param Query\Clauses\AbstractClause $clause
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function buildAbstractClause(Query\Clauses\AbstractClause $clause)
	{
		if ($clause instanceof Query\Clauses\SelectClause)
		{
			$parts = array($clause->getName());
			if ($clause->getQuantifier() === Query\Clauses\SelectClause::QUANTIFIER_DISTINCT)
			{
				$parts[] = Query\Clauses\SelectClause::QUANTIFIER_DISTINCT;
			}
			$selectList = $clause->getSelectList();
			if ($selectList === null || $selectList->count() == 0)
			{
				$selectList = new Query\Expressions\AllColumns();
			}

			$parts[] = $this->buildSQLFragment($selectList);
			return implode(' ', $parts);
		}
		elseif ($clause instanceof Query\Clauses\FromClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getTableExpression()));
			$parts[] = implode(' ', $this->buildSQLFragmentArray($clause->getJoins()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof Query\Clauses\WhereClause)
		{
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getPredicate()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof Query\Clauses\OrderByClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof Query\Clauses\GroupByClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof Query\Clauses\HavingClause)
		{
			return 'HAVING ' . $this->buildSQLFragment($clause->getPredicate());
		}
		elseif ($clause instanceof Query\Clauses\InsertClause)
		{
			$clause->checkCompile();
			$insert = 'INSERT INTO ' . $this->buildSQLFragment($clause->getTable());
			$columns = $clause->getColumns();
			if (count($columns))
			{
				$compiler = $this;
				$insert .= ' (' . implode(', ', array_map(function ($column) use($compiler)
					{
						return $compiler->buildSQLFragment($column);
					}, $columns)) . ')';
			}
			return $insert;
		}
		elseif ($clause instanceof Query\Clauses\ValuesClause)
		{
			$clause->checkCompile();
			return 'VALUES (' . $this->buildSQLFragment($clause->getValuesList()) . ')';
		}
		elseif ($clause instanceof Query\Clauses\UpdateClause)
		{
			$clause->checkCompile();
			return 'UPDATE ' . $this->buildSQLFragment($clause->getTable());
		}
		elseif ($clause instanceof Query\Clauses\SetClause)
		{
			$clause->checkCompile();
			return 'SET ' . $this->buildSQLFragment($clause->getSetList());
		}
		elseif ($clause instanceof Query\Clauses\DeleteClause)
		{
			return 'DELETE';
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid clause', 999999);
		}
	}
}
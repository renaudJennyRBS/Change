<?php
namespace Change\Db\SQLite;

use Change\Db\Query;
use Change\Db\ScalarType;

/**
 * @name \Change\Db\SQLite\FragmentBuilder
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
		$className = get_class($fragment);
		switch ($className)
		{
			case 'Change\Db\Query\Expressions\Table':
				/* @var $fragment \Change\Db\Query\Expressions\Table */
				$identifierParts = array();
				$dbName = $fragment->getDatabase();
				$tableName = $fragment->getName();
				if (!empty($dbName))
				{
					$identifierParts[] = '[' . $dbName . ']';
				}
				$identifierParts[] = '[' . $tableName . ']';
				return implode('.', $identifierParts);

			case 'Change\Db\Query\Expressions\Column':
				/* @var $fragment \Change\Db\Query\Expressions\Column */
				$columnName = $this->buildSQLFragment($fragment->getColumnName());
				$tableOrIdentifier = $fragment->getTableOrIdentifier();
				$table = ($tableOrIdentifier) ? $this->buildSQLFragment($tableOrIdentifier) : null;
				return empty($table) ? $columnName : $table . '.' . $columnName;

			case 'Change\Db\Query\Expressions\Parentheses':
				/* @var $fragment \Change\Db\Query\Expressions\Parentheses */
				return '(' . $this->buildSQLFragment($fragment->getExpression()) . ')';

			case 'Change\Db\Query\Expressions\Identifier':
				/* @var $fragment \Change\Db\Query\Expressions\Identifier */
				return implode('.', array_map(function ($part)
				{
					return '[' . $part . ']';
				}, $fragment->getParts()));

			case 'Change\Db\Query\Expressions\Concat':
				/* @var $fragment \Change\Db\Query\Expressions\Concat */
				return implode(' || ', $this->buildSQLFragmentArray($fragment->getList()));

			case 'Change\Db\Query\Expressions\ExpressionList':
				/* @var $fragment \Change\Db\Query\Expressions\ExpressionList */
				return implode(', ', $this->buildSQLFragmentArray($fragment->getList()));

			case 'Change\Db\Query\Expressions\Raw':
			case 'Change\Db\Query\Expressions\AllColumns':
				return $fragment->toSQL92String();

			case 'Change\Db\Query\Predicates\Conjunction':
				/* @var $fragment \Change\Db\Query\Predicates\Conjunction */
				return '(' . implode(' AND ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';

			case 'Change\Db\Query\Predicates\Disjunction':
				/* @var $fragment \Change\Db\Query\Predicates\Disjunction */
				return '(' . implode(' OR ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';

			case 'Change\Db\Query\Predicates\Like':
				/* @var $fragment \Change\Db\Query\Predicates\Like */
				$fragment->checkCompile();
				if ($fragment->getCaseSensitive())
				{
					$fragment->setOperator('GLOB');
					$fragment->setWildCard('*');
				}

				$rhe = $fragment->getCompletedRightHandExpression();
				return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' '
				. $this->buildSQLFragment($rhe);

			case 'Change\Db\Query\Predicates\In':
				/* @var $fragment \Change\Db\Query\Predicates\In */
				$fragment->checkCompile();
				$rhe = $fragment->getCompletedRightHandExpression();
				return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' '
				. $this->buildSQLFragment($rhe);

			case 'Change\Db\Query\Predicates\Exists':
				/* @var $fragment \Change\Db\Query\Predicates\Exists */
				$fragment->checkCompile();
				return $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getExpression());

			case 'Change\Db\Query\Predicates\HasPermission':
				/* @var $fragment \Change\Db\Query\Predicates\HasPermission */
				return $this->buildSQLFragment($fragment->getPredicate());

			case 'Change\Db\Query\Expressions\BinaryOperation':
			case 'Change\Db\Query\Expressions\Alias':
			case 'Change\Db\Query\Expressions\Assignment':
			case 'Change\Db\Query\Predicates\BinaryPredicate':
				/* @var $fragment \Change\Db\Query\Expressions\BinaryOperation */
				return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' '
				. $this->buildSQLFragment($fragment->getRightHandExpression());

			case 'Change\Db\Query\Predicates\UnaryPredicate':
				/* @var $fragment \Change\Db\Query\Predicates\UnaryPredicate */
				return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();

			case 'Change\Db\Query\Expressions\OrderingSpecification':
				/* @var $fragment \Change\Db\Query\Expressions\OrderingSpecification */
				return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();

			case 'Change\Db\Query\Expressions\UnaryOperation':
				/* @var $fragment \Change\Db\Query\Expressions\UnaryOperation */
				return $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getExpression());

			case 'Change\Db\Query\Expressions\Join':
				/* @var $fragment \Change\Db\Query\Expressions\Join */

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
							throw new \RuntimeException('RIGHT OUTER JOIN Is not supported', 42038);
							break;
						case Query\Expressions\Join::FULL_OUTER_JOIN :
							throw new \RuntimeException('FULL OUTER JOIN Is not supported', 42039);
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

			case 'Change\Db\Query\Expressions\Value':
			case 'Change\Db\Query\Expressions\String':
			case 'Change\Db\Query\Expressions\Numeric':
				/* @var $fragment \Change\Db\Query\Expressions\Value */
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

			case 'Change\Db\Query\Expressions\Parameter':
				/* @var $fragment \Change\Db\Query\Expressions\Parameter */
				return ':' . $fragment->getName();

			case 'Change\Db\Query\Expressions\SubQuery':
				/* @var $fragment \Change\Db\Query\Expressions\SubQuery */
				return '(' . $this->buildQuery($fragment->getSubQuery()) . ')';

			case 'Change\Db\Query\Expressions\Func':
				/* @var $fragment \Change\Db\Query\Expressions\Func */
				return $this->buildSQLFunc($fragment);

			case 'Change\Db\Query\SelectQuery':
			case 'Change\Db\Query\InsertQuery':
			case 'Change\Db\Query\UpdateQuery':
			case 'Change\Db\Query\DeleteQuery':
				/* @var $fragment \Change\Db\Query\AbstractQuery */
				return $this->buildQuery($fragment);

			case 'Change\Db\Query\Clauses\WhereClause':
			case 'Change\Db\Query\Clauses\CollateClause':
			case 'Change\Db\Query\Clauses\DeleteClause':
			case 'Change\Db\Query\Clauses\FromClause':
			case 'Change\Db\Query\Clauses\GroupByClause':
			case 'Change\Db\Query\Clauses\HavingClause':
			case 'Change\Db\Query\Clauses\InsertClause':
			case 'Change\Db\Query\Clauses\OrderByClause':
			case 'Change\Db\Query\Clauses\SelectClause':
			case 'Change\Db\Query\Clauses\SetClause':
			case 'Change\Db\Query\Clauses\UpdateClause':
			case 'Change\Db\Query\Clauses\ValuesClause':
				/* @var $fragment \Change\Db\Query\Clauses\AbstractClause */
				return $this->buildAbstractClause($fragment);

			default:
				return $this->dbProvider->buildCustomSQLFragment($fragment);
		}
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
		return $func->getFunctionName() . '(' . implode(',', $this->buildSQLFragmentArray($func->getArguments())) . ')';
	}

	/**
	 * @param Query\AbstractQuery $query
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function buildQuery(Query\AbstractQuery $query)
	{
		$className = get_class($query);
		switch ($className)
		{
			case 'Change\Db\Query\SelectQuery':
				/* @var $query \Change\Db\Query\SelectQuery */
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
					$parts[] = strval(max(1, $query->getMaxResults()));
					if ($query->getStartIndex())
					{
						$parts[] = ' OFFSET ' . strval(max(0, $query->getStartIndex()));
					}
				}

				return implode(' ', $parts);

			case 'Change\Db\Query\InsertQuery':
				/* @var $query \Change\Db\Query\InsertQuery */
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

			case 'Change\Db\Query\UpdateQuery':
				/* @var $query \Change\Db\Query\UpdateQuery */
				$query->checkCompile();
				$parts = array($this->buildAbstractClause($query->getUpdateClause()),
					$this->buildAbstractClause($query->getSetClause()));
				if ($query->getWhereClause() !== null)
				{
					$parts[] = $this->buildAbstractClause($query->getWhereClause());
				}
				return implode(' ', $parts);

			case 'Change\Db\Query\DeleteQuery':
				/* @var $query \Change\Db\Query\DeleteQuery */
				$query->checkCompile();
				$parts = array($this->buildAbstractClause($query->getDeleteClause()),
					$this->buildAbstractClause($query->getFromClause()));
				if ($query->getWhereClause() !== null)
				{
					$parts[] = $this->buildAbstractClause($query->getWhereClause());
				}
				return implode(' ', $parts);

			default:
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
		$className = get_class($clause);
		switch ($className)
		{
			case 'Change\Db\Query\Clauses\SelectClause':
				/* @var $clause \Change\Db\Query\Clauses\SelectClause */
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

			case 'Change\Db\Query\Clauses\FromClause':
				/* @var $clause \Change\Db\Query\Clauses\FromClause */
				$clause->checkCompile();
				$parts = array($clause->getName(), $this->buildSQLFragment($clause->getTableExpression()));
				$parts[] = implode(' ', $this->buildSQLFragmentArray($clause->getJoins()));
				return implode(' ', $parts);

			case 'Change\Db\Query\Clauses\WhereClause':
				/* @var $clause \Change\Db\Query\Clauses\WhereClause */
				$parts = array($clause->getName(), $this->buildSQLFragment($clause->getPredicate()));
				return implode(' ', $parts);

			case 'Change\Db\Query\Clauses\OrderByClause':
				/* @var $clause \Change\Db\Query\Clauses\OrderByClause */
				$clause->checkCompile();
				$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
				return implode(' ', $parts);

			case 'Change\Db\Query\Clauses\GroupByClause':
				/* @var $clause \Change\Db\Query\Clauses\GroupByClause */
				$clause->checkCompile();
				$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
				return implode(' ', $parts);

			case 'Change\Db\Query\Clauses\HavingClause':
				/* @var $clause \Change\Db\Query\Clauses\HavingClause */
				return 'HAVING ' . $this->buildSQLFragment($clause->getPredicate());

			case 'Change\Db\Query\Clauses\InsertClause':
				/* @var $clause \Change\Db\Query\Clauses\InsertClause */
				$clause->checkCompile();
				$insert = 'INSERT INTO ' . $this->buildSQLFragment($clause->getTable());
				$columns = $clause->getColumns();
				if (count($columns))
				{
					$compiler = $this;
					$insert .= ' (' . implode(', ', array_map(function ($column) use ($compiler)
						{
							return $compiler->buildSQLFragment($column);
						}, $columns)) . ')';
				}
				return $insert;

			case 'Change\Db\Query\Clauses\ValuesClause':
				/* @var $clause \Change\Db\Query\Clauses\ValuesClause */
				$clause->checkCompile();
				return 'VALUES (' . $this->buildSQLFragment($clause->getValuesList()) . ')';

			case 'Change\Db\Query\Clauses\UpdateClause':
				/* @var $clause \Change\Db\Query\Clauses\UpdateClause */
				$clause->checkCompile();
				return 'UPDATE ' . $this->buildSQLFragment($clause->getTable());

			case 'Change\Db\Query\Clauses\SetClause':
				/* @var $clause \Change\Db\Query\Clauses\SetClause */
				$clause->checkCompile();
				return 'SET ' . $this->buildSQLFragment($clause->getSetList());

			case 'Change\Db\Query\Clauses\DeleteClause':
				/* @var $clause \Change\Db\Query\Clauses\DeleteClause */
				return 'DELETE';

			default:
				throw new \InvalidArgumentException('Argument 1 must be a valid clause', 999999);
		}
	}
}
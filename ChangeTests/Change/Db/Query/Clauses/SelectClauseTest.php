<?php

namespace ChangeTests\Change\Db\Query\Clauses;

class SelectClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleSelects()
	{
		// SELECT * FROM table1
		$table = new \Change\Db\Query\Objects\Table("table1");
		$select = new \Change\Db\Query\Clauses\SelectClause(null,  new \Change\Db\Query\Clauses\FromClause($table));
		$this->assertEquals('SELECT * FROM "table1"', $select->toSQL92String());
		
		// SELECT * FROM table1, table2, table3
		$columns = new \Change\Db\Query\Expressions\ExpressionList(array(
				new \Change\Db\Query\Expressions\Column("c1"),
				new \Change\Db\Query\Expressions\Column("c2"),
				new \Change\Db\Query\Expressions\Column("c3")
		));
		$select = new \Change\Db\Query\Clauses\SelectClause($columns,  new \Change\Db\Query\Clauses\FromClause($table));
		$this->assertEquals('SELECT "c1", "c2", "c3" FROM "table1"', $select->toSQL92String());
		
		$columns = new \Change\Db\Query\Expressions\ExpressionList(array(
			new \Change\Db\Query\Expressions\Column("c1", "a"),
			new \Change\Db\Query\Expressions\Column("c2", "b"),
			new \Change\Db\Query\Expressions\Column("c3", "c")
		));
		$select = new \Change\Db\Query\Clauses\SelectClause($columns,  new \Change\Db\Query\Clauses\FromClause($table));
		$this->assertEquals('SELECT "c1" AS "a", "c2" AS "b", "c3" AS "c" FROM "table1"', $select->toSQL92String());
		
		
		//$sql = "SELECT SUM(stock) AS SUM FROM `m_mysqlstock_mod_mvt` WHERE `sku_id` = :sku_id AND `warehouse_id` = :warehouse_id";
		$table = new \Change\Db\Query\Objects\Table("m_mysqlstock_mod_mvt");
		$selectList = new \Change\Db\Query\Expressions\ExpressionList(array(new \Change\Db\Query\Expressions\BinaryOperation(
				new \Change\Db\Query\Expressions\Func("SUM", array(new \Change\Db\Query\Expressions\Column("stock"))),
				new \Change\Db\Query\Expressions\String("SUM"),
				"AS"
			)));
		
		$predicate1 = new \Change\Db\Query\Predicates\Eq(new \Change\Db\Query\Expressions\Column("sku_id"), new \Change\Db\Query\Expressions\Raw(":sku_id"));
		$predicate2 = new \Change\Db\Query\Predicates\Eq(new \Change\Db\Query\Expressions\Column("warehouse_id"), new \Change\Db\Query\Expressions\Raw(":warehouse_id"));
		$predicate = new \Change\Db\Query\Predicates\Conjunction($predicate1, $predicate2);
		$whereClause = new \Change\Db\Query\Clauses\WhereClause($predicate);
		$select = new \Change\Db\Query\Clauses\SelectClause($selectList,  new \Change\Db\Query\Clauses\FromClause($table), $whereClause);
		echo $select->toSQL92String();
		
		// "SELECT DISTINCT `document_id` FROM `m_catalog_doc_product` INNER JOIN `m_mysqlstock_mod_product` ON `code_sku` = `codesku` WHERE `sku_id` IN (".implode(',', $chunk).")";
	}
}
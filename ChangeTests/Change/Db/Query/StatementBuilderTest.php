<?php

namespace ChangeTests\Change\Db\Query;

class StatementBuilderTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder()
	{
		return new \Change\Db\Query\StatementBuilder($this->getApplication()->getApplicationServices()->getDbProvider());
	}
	
	public function testConstruct()
	{
		$instance = $this->getNewStatementBuilder();
		$this->assertTrue(true);
		
		try
		{
			$instance->insertQuery();
			$this->fail('Call insert() before');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Call insert() before', $e->getMessage());
		}	

		try
		{
			$instance->updateQuery();
			$this->fail('Call update() before');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Call update() before', $e->getMessage());
		}
		
		try
		{
			$instance->deleteQuery();
			$this->fail('Call delete() before');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Call delete() before', $e->getMessage());
		}	
	}
	
	public function testGetFragmentBuilder()
	{
		$instance = $this->getNewStatementBuilder();
		$this->assertInstanceOf('\Change\Db\Query\SQLFragmentBuilder', $instance->getFragmentBuilder());
	}
	
	
	public function testAddParameter()
	{
		$instance = $this->getNewStatementBuilder();
		try
		{
			$instance->addParameter('test');
			$this->fail('Query not initialized');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Query not initialized', $e->getMessage());
		}
		
		$instance->insert('test');
		$instance->addParameter('test');
		
		try
		{
			$instance->addParameter(array('test'));
			$this->fail('Query not initialized');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('argument must be a string', $e->getMessage());
		}
	}

	
	public function testInsert()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert();
		$this->assertNull($instance->insertQuery()->getInsertClause());
		
		$instance->insert('test');
		$ic = $instance->insertQuery()->getInsertClause();
		$this->assertInstanceOf('\Change\Db\Query\Clauses\InsertClause', $ic);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $ic->getTable());
		$this->assertEquals('test', $ic->getTable()->getName());
		$this->assertCount(0, $ic->getColumns());
		
		$instance->insert('test', 'f1');
		$q = $instance->insertQuery();
		$this->assertCount(1, $q->getInsertClause()->getColumns());
	}
	
	public function testAddColumns()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert('test');
		$instance->addColumns('c1', 'c2');
		$q = $instance->insertQuery();
		$this->assertCount(2, $q->getInsertClause()->getColumns());
	}

	public function testAddColumn()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert('test');
		$instance->addColumn('c1');
		$q = $instance->insertQuery();
		$this->assertCount(1, $q->getInsertClause()->getColumns());
		
		$instance->addColumn('c2');
		$this->assertCount(2, $q->getInsertClause()->getColumns());
	}
	
	public function testAddValues()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert('test');
		$instance->addValues('t1', 't2');
		$q = $instance->insertQuery();
		$this->assertEquals(2, $q->getValuesClause()->getValuesList()->count());
	}
	
	public function testAddValue()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert('test');
		$instance->addValue('t1');
		$q = $instance->insertQuery();
		$this->assertEquals(1, $q->getValuesClause()->getValuesList()->count());
		
		$instance->addValue('t1');
		$this->assertEquals(2, $q->getValuesClause()->getValuesList()->count());
	}
	
	
	public function testUpdate()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->update();
		$this->assertNull($instance->updateQuery()->getUpdateClause());
	
		$instance->update('test');
		$c = $instance->updateQuery()->getUpdateClause();
		$this->assertInstanceOf('\Change\Db\Query\Clauses\UpdateClause', $c);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $c->getTable());
		$this->assertEquals('test', $c->getTable()->getName());
	}
	
	public function testAssign()
	{
		$instance = $this->getNewStatementBuilder();	
		$instance->update('test');
		$instance->assign('c1', 'text1');
		$c = $instance->updateQuery()->getSetClause();
		$l = $c->getSetList();
		$this->assertEquals(1, $l->count());
		$array = $l->getList();
		$a1 = $array[0];
		/* @var $a1 \Change\Db\Query\Expressions\Assignment */
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Assignment', $a1);
		
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Column', $a1->getLeftHandExpression());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\String', $a1->getRightHandExpression());
	}

	public function testDelete()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->delete();
		$this->assertNull($instance->deleteQuery()->getFromClause());
	
		$instance->delete('test');
		$c = $instance->deleteQuery()->getFromClause();
		$this->assertInstanceOf('\Change\Db\Query\Clauses\FromClause', $c);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $c->getTableExpression());
	}
	
	public function testWhere()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->delete();	
		$instance->where($instance->getFragmentBuilder()->eq('i1', 'i2'));
		$c = $instance->deleteQuery()->getWhereClause();
		$this->assertInstanceOf('\Change\Db\Query\Clauses\WhereClause', $c);
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $c->getPredicate());
		
		$instance = $this->getNewStatementBuilder();
		$instance->update();
		$instance->where($instance->getFragmentBuilder()->eq('i1', 'i2'));
		$c = $instance->updateQuery()->getWhereClause();
		$this->assertInstanceOf('\Change\Db\Query\Clauses\WhereClause', $c);
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $c->getPredicate());
	}
		
	public function testReset()
	{
		$instance = $this->getNewStatementBuilder();
		$instance->insert('test');
		
		$instance->reset();
	
		try
		{
			$instance->insertQuery();
			$this->fail('Call insert() before');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Call insert() before', $e->getMessage());
		}
	}
}

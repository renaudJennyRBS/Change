<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\DeleteClause;

class DeleteClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new DeleteClause();
		$this->assertEquals('DELETE', $i->getName());	
	}
	
	public function testToSQL92String()
	{		
		$i = new DeleteClause();
		$this->assertEquals("DELETE", $i->toSQL92String());
	}
}

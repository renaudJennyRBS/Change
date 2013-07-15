<?php
namespace ChangeTests\Change\Db\Query\Predicates;

use Change\Db\Query\Expressions;
use Change\Db\Query\Predicates\HasPermission;

class HasPermissionTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testConstruct()
	{
		$obj = new HasPermission();
		$this->assertInstanceOf('\Change\Db\Query\Predicates\HasPermission', $obj);
		$this->assertNull($obj->getAccessor());
		$this->assertNull($obj->getRole());
		$this->assertNull($obj->getPrivilege());
		$this->assertNull($obj->getResource());
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}

	public function testAccessor()
	{
		$obj = new HasPermission(new Expressions\Numeric(45));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Numeric', $obj->getAccessor());
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE (("accessor_id" = 45 OR "accessor_id" = 0) AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list = new Expressions\ExpressionList();
		$list->add(new Expressions\Numeric(45));
		$obj->setAccessor($list);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $obj->getAccessor());

		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" IN (45, 0) AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list->add(new Expressions\Numeric(100));
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" IN (45, 100, 0) AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}

	public function testRole()
	{
		$obj = new HasPermission(null, new Expressions\String('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\String', $obj->getRole());
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND ("role" = \'test\' OR "role" = \'*\') AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list = new Expressions\ExpressionList();
		$list->add(new Expressions\String('test'));
		$obj->setRole($list);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $obj->getRole());

		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" IN (\'test\', \'*\') AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list->add(new Expressions\String('t'));
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" IN (\'test\', \'t\', \'*\') AND "resource_id" = 0 AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}

	public function testResource()
	{
		$obj = new HasPermission(null, null, new Expressions\Numeric(45));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Numeric', $obj->getResource());
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND ("resource_id" = 45 OR "resource_id" = 0) AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list = new Expressions\ExpressionList();
		$list->add(new Expressions\Numeric(45));
		$obj->setResource($list);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $obj->getResource());

		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" IN (45, 0) AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list->add(new Expressions\Numeric(100));
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" IN (45, 100, 0) AND "privilege" = \'*\'))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}

	public function testPrivilege()
	{
		$obj = new HasPermission(null, null, null, new Expressions\String('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\String', $obj->getPrivilege());
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" = 0 AND ("privilege" = \'test\' OR "privilege" = \'*\')))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list = new Expressions\ExpressionList();
		$list->add(new Expressions\String('test'));
		$obj->setPrivilege($list);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $obj->getPrivilege());

		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" IN (\'test\', \'*\')))';
		$this->assertEquals($sql, $obj->toSQL92String());

		$list->add(new Expressions\String('t'));
		$sql = 'EXISTS(SELECT * FROM "change_permission_rule" WHERE ("accessor_id" = 0 AND "role" = \'*\' AND "resource_id" = 0 AND "privilege" IN (\'test\', \'t\', \'*\')))';
		$this->assertEquals($sql, $obj->toSQL92String());
	}
}

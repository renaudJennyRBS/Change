<?php

use Change\Http\Event;
use Change\Http\Request;

class AddPermissionRulesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testExecute()
	{
		$permissionRules = array(
			'accessor_id' => 123456,
			'roles' => array('Administrator'),
			'privileges' => array('Rbs_User_User'),
			'resources' => array(123456)
		);

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$event->setPermissionsManager($pm);
		$paramArray = array('permissionRules' => $permissionRules);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\AddPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check the added permissions
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		$this->assertCount(1, $arrayResult);
		//Check this new rule with permissionManager
		$this->assertTrue($pm->hasRule(123456, 'Administrator', 123456, 'Rbs_User_User'));

		//insert more rules and test if the function delete useless rules works like expected
		$permissionRules = array(
			'accessor_id' => 123456,
			'roles' => array('Creator', 'Editor', 'Publisher'),
			'privileges' => array('Rbs_User_Group', 'Rbs_Media_Image', 'Rbs_Website_StaticPage'),
			'resources' => array(123, 456, 789)
		);
		//this array will create 3x3x3 (27) rules!

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$event->setPermissionsManager($pm);
		$paramArray = array('permissionRules' => $permissionRules);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\AddPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check the added permissions
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		//our 27 new rules + the older: 28
		$this->assertCount(28, $arrayResult);
		//check just one rule
		$this->assertTrue($pm->hasRule(123456, 'Creator', 789, 'Rbs_Media_Image'));

		//now we insert a rule with a better permission
		$permissionRules = array(
			'accessor_id' => 123456,
			'roles' => array('Creator'),
			'privileges' => array('Rbs_User_Group'),
			'resources' => array('*')
		);
		//this rule will make 3 older rules useless.

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$event->setPermissionsManager($pm);
		$paramArray = array('permissionRules' => $permissionRules);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\AddPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check the added permissions
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		//28 rules + 1 new - 3 useless = 26 rules
		$this->assertCount(26, $arrayResult);
		$this->assertTrue($pm->hasRule(123456, 'Creator', '*', 'Rbs_User_Group'));
		$this->assertFalse($pm->hasRule(123456, 'Creator', 123, 'Rbs_User_Group'));

		//with a very better permission
		$permissionRules = array(
			'accessor_id' => 123456,
			'roles' => array('Creator', 'Editor'),
			'privileges' => array('*'),
			'resources' => array('*')
		);
		//this rule will make the 9 Editor's older rules and 7 Creator's older rules useless (total: 16).

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$event->setPermissionsManager($pm);
		$paramArray = array('permissionRules' => $permissionRules);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\AddPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check the added permissions
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		//26 rules + 2 new - 16 useless = 12 rules
		$this->assertCount(12, $arrayResult);
		//check one new rule
		$this->assertTrue($pm->hasRule(123456, 'Editor', '*', '*'));
		$this->assertFalse($pm->hasRule(123456, 'Creator', '*', 'Rbs_User_Group'));

		//give a VIP **Gold** prime ultra full access \o/
		$permissionRules = array(
			'accessor_id' => 123456,
			'roles' => array('*'),
			'privileges' => array('*'),
			'resources' => array('*')
		);
		//this rule will make all old rules useless!

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$event->setPermissionsManager($pm);
		$paramArray = array('permissionRules' => $permissionRules);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\AddPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check the added permissions
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		//only one rule!
		$this->assertCount(1, $arrayResult);
		//check the rule
		$this->assertTrue($pm->hasRule(123456, '*', '*', '*'));
	}
}
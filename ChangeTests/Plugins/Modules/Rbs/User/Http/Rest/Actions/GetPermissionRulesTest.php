<?php

use Change\Http\Event;
use Change\Http\Request;

class GetPermissionRulesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function setUp()
	{
		//add fake permissions
		$pm = new \Change\Permissions\PermissionsManager();
		$pm->setApplicationServices($this->getApplicationServices());
		$pm->addRule(123456, 'Administrator', 123456, 'Rbs_User_User');
		$pm->addRule(123456, 'Administrator', '*', 'Rbs_User_Group');
		$pm->addRule(123456, 'Editor', 1234567, 'Rbs_Media_Image');
		$pm->addRule(123456, 'Consumer', '*' , '*');
		$pm->addRule(123456, 'Publisher', '*', 'Rbs_Collection_Collection');
		//not the same accessor id
		$pm->addRule(123457, 'Creator', '*', 'Rbs_Collection_Collection');
		$pm->addRule(123457, 'Creator', '*', 'Rbs_Collection_Collection');
	}

	public function testExecute()
	{
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
		$this->assertCount(5, $arrayResult, 'if it is 7, that mean that all permission are taken et not only them for targeted user');
		//take the first one to check if all key exist
		$permission = $arrayResult[0];
		$this->assertArrayHasKey('rule_id', $permission);
		$this->assertArrayHasKey('role', $permission);
		$this->assertArrayHasKey('resource_id', $permission);
		$this->assertArrayHasKey('privilege', $permission);
	}
}
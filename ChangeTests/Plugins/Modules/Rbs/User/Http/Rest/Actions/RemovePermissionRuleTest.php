<?php

use Change\Http\Event;
use Change\Http\Request;

class RemovePermissionRuleTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$pm = $this->getApplicationServices()->getPermissionsManager();

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
		$event->setParams($this->getDefaultEventArguments());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$firstArrayResult = $result->toArray();
		$this->assertNotEmpty($firstArrayResult);
		$this->assertCount(5, $firstArrayResult);
		$firstRule = array_pop($firstArrayResult);

		//delete this rule
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$paramArray = array('rule_id' => $firstRule['rule_id']);
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\RemovePermissionRule();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		//check if the rule is actually deleted
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$paramArray = array('accessorId' => 123456);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getPermissionRules = new \Rbs\User\Http\Rest\Actions\GetPermissionRules();
		$getPermissionRules->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$secondArrayResult = $result->toArray();
		$this->assertNotEmpty($secondArrayResult);
		$this->assertCount(4, $secondArrayResult);

		$this->assertEquals($firstArrayResult, $secondArrayResult);
	}
}
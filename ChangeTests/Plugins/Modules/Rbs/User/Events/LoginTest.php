<?php
namespace ChangeTests\Rbs\User\Events;

use Rbs\User\Events\Login;
use Change\Events\Event;

/**
 * @name \ChangeTests\Rbs\User\Events\LoginTest
 */
class LoginTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		$this->getApplicationServices()->getTransactionManager()->commit();
		parent::tearDown();
	}

	public function testLogin()
	{
		$applicationServices = $this->getApplicationServices();
		/* @var $grp \Rbs\User\Documents\Group */
		$grp = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_Group');
		$grp->setLabel('Test 1');
		$grp->setRealm('test');
		$grp->save();

		/* @var $user \Rbs\User\Documents\User */
		$user = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
		$user->setLogin('login');
		$user->setEmail('fake@temporary.fr');
		$user->setPassword('Un password');
		$user->setGroups(array($grp));
		$user->setActive(true);
		$user->save();

		$args = array('login' => 'login', 'password' => 'Un password', 'realm' => 'test');
		$event = new Event('login', $this, $args + $this->getDefaultEventArguments());

		$obj = new Login();
		$obj->execute($event);

		/* @var $u \Rbs\User\Documents\User */
		$u = $event->getParam('user');
		$this->assertInstanceOf('\Change\User\UserInterface', $u);
		$this->assertEquals($user->getId(), $u->getId());


		$args = array('login' => 'notfound', 'password' => 'Un password', 'realm' => 'test');
		$event = new Event(\Change\User\AuthenticationManager::EVENT_LOGIN, $this, $args + $this->getDefaultEventArguments());

		$obj = new Login();
		$obj->execute($event);
		$this->assertNull($event->getParam('user'));
	}
}
<?php
namespace ChangeTests\Rbs\User\Events;

use Rbs\User\Events\Login;
use Zend\EventManager\Event;
/**
 * Class LoginTest
 * @package ChangeTests\Rbs\User\Events
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
		parent::tearDown();
		$this->getApplicationServices()->getTransactionManager()->commit();
		$this->closeDbConnection();
	}

	public function testLogin()
	{
		$ds = $this->getDocumentServices();
		/* @var $grp \Rbs\User\Documents\Group */
		$grp = $ds->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_Group');
		$grp->setLabel('Test 1');
		$grp->setRealm('test');
		$grp->save();

		/* @var $user \Rbs\User\Documents\User */
		$user = $ds->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
		$user->setLabel('user 1');
		$user->setLogin('login de test');
		$user->setEmail('fake@temporary.fr');
		$user->setPassword('Un password');
		$user->setGroups(array($grp));
		$user->setActive(true);
		$user->save();

		$args = array('login' => 'login de test', 'password' => 'Un password', 'realm' => 'test');
		$args['documentServices'] = $this->getDocumentServices();
		$event = new Event('login', $this, $args);

		$obj = new Login();
		$obj->execute($event);

		/* @var $u \Rbs\User\Documents\User */
		$u = $event->getParam('user');
		$this->assertInstanceOf('\Change\User\UserInterface', $u);
		$this->assertEquals($user->getId(), $u->getId());


		$args = array('login' => 'not found', 'password' => 'Un password', 'realm' => 'test');
		$args['documentServices'] = $this->getDocumentServices();
		$event = new Event(\Change\User\AuthenticationManager::EVENT_LOGIN, $this, $args);

		$obj = new Login();
		$obj->execute($event);
		$this->assertNull($event->getParam('user'));
	}
}
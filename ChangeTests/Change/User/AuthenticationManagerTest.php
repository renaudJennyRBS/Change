<?php
namespace ChangeTests\Change\User;

use Change\User\AuthenticationManager;
use Change\User\UserInterface;

/**
 * @name \ChangeTests\Change\User\AuthenticationManagerTest
 */
class AuthenticationManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return AuthenticationManager
	 */
	protected function getAuthenticationManager()
	{
		return $this->getApplicationServices()->getAuthenticationManager();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('Change\User\AuthenticationManager', $this->getAuthenticationManager());
	}

	public function testLogin()
	{
		$am = $this->getAuthenticationManager();

		$this->assertNull($am->login('testLogin', 'testPWD', 'testRealm'));

		$callback = function(\Zend\EventManager\Event $event) {
			if ($event->getParam('login') === 'testLogin' &&
				$event->getParam('password') == 'testPWD' &&
				$event->getParam('realm') == 'testRealm')
			{
				$event->setParam('user', new User_2124512348());
			}
		};

		$toDetach = $am->getEventManager()->attach(AuthenticationManager::EVENT_LOGIN, $callback);
		$u = $am->login('testLogin', 'testPWD', 'testRealm');
		$this->assertInstanceOf('Change\User\UserInterface', $u);
		$this->assertEquals('User_2124512348', $u->getName());
		$am->getEventManager()->detach($toDetach);

		$this->assertNull($am->login('testLogin', 'testPWD', 'testRealm'));
	}

	public function testCurrentUser()
	{
		$am = $this->getAuthenticationManager();
		$this->assertInstanceOf('\Change\User\AnonymousUser', $am->getCurrentUser());
		$this->assertFalse($am->getCurrentUser()->authenticated());
		$u = new User_2124512348();
		$am->setCurrentUser($u);

		$this->assertSame($u, $am->getCurrentUser());

		$am->setCurrentUser(null);
		$this->assertInstanceOf('\Change\User\AnonymousUser', $am->getCurrentUser());
	}
}

class User_2124512348 implements UserInterface
{

	/**
	 * @return integer
	 */
	public function getId()
	{
		return 1;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'User_2124512348';
	}

	/**
	 * @return \Change\User\GroupInterface[]
	 */
	public function getGroups()
	{
		return array();
	}

	/**
	 * @return boolean
	 */
	public function authenticated()
	{
		return true;
	}
}
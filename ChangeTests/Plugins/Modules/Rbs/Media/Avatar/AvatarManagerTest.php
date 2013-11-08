<?php
namespace ChangeTests\Rbs\Media\Avatar;

use Zend\EventManager\EventManagerInterface;

class AvatarManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	protected function getGenericServices()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('genericServices', $genericServices);
		return $genericServices;
	}


	public function testGetAvatarUrl()
	{
		$baseURL = 'http://www.rbs.fr';
		$urlManager = new \Change\Http\UrlManager(new \Zend\Uri\Http($baseURL));

		$avatarManager = $this->getGenericServices()->getAvatarManager();
		$this->assertInstanceOf('Rbs\Media\Avatar\AvatarManager', $avatarManager);
		$avatarManager->setUrlManager($urlManager);

		$url = $avatarManager->getAvatarUrl(90, 'test@test.com');
		$this->assertNotNull($url);

		$user = new User_2124512347();
		$url = $avatarManager->getAvatarUrl(90, null, $user);
		$this->assertNotNull($url);

		$callback = function ($event)
		{
			$event->setParam('url', $event->getParam('email'));
			$event->stopPropagation();
		};

		$attach = $avatarManager->getEventManager()
			->attach(\Rbs\Media\Avatar\AvatarManager::AVATAR_GET_AVATAR_URL, $callback, 10);
		$url = $avatarManager->getAvatarUrl(90, 'test@test.com');
		$this->assertEquals('test@test.com', $url);
		$avatarManager->getEventManager()->detach($attach);

		$url = $avatarManager->getAvatarUrl(90, 'test@test.com');
		$this->assertNotEquals('test@test.com', $url);

		$this->getApplication()->getConfiguration()
			->addVolatileEntry('Rbs/Media/AvatarManager/Test', 'ChangeTests\Rbs\Media\Avatar\Listener_354651321');
		$avatarManager->clearEventManager();

		$url = $avatarManager->getAvatarUrl(90, 'test@test.com');
		$this->assertEquals('test@test.com', $url);
	}
}

class User_2124512347 extends \Rbs\User\Documents\User
{

	public function __construct()
	{
	}

	public function getEmail()
	{
		return 'usertest@test.com';
	}
}

class Listener_354651321 implements \Zend\EventManager\ListenerAggregateInterface
{

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{

		$callback = function ($event)
		{
			$event->setParam('url', $event->getParam('email'));
			$event->stopPropagation();
		};

		$events->attach(\Rbs\Media\Avatar\AvatarManager::AVATAR_GET_AVATAR_URL, $callback, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
<?php
namespace ChangeTests\Change\Http\Rest\OAuth;

use Change\Http\Event as HttpEvent;
use Change\Http\Rest\OAuth\AuthenticationListener;

/**
 * @name \ChangeTests\Change\Http\Rest\OAuth\AuthenticationListenerTest
 */
class AuthenticationListenerTest extends \ChangeTests\Change\TestAssets\TestCase
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

	/**
	 * @return \Change\Http\Rest\OAuth\AuthenticationListener
	 */
	public function testConstruct()
	{
		$authenticationListener = new AuthenticationListener();
		$this->assertInstanceOf('\Change\Http\Rest\OAuth\AuthenticationListener', $authenticationListener);
		return $authenticationListener;
	}

	protected function setConsumerForTest()
	{
		$isb = $this->getApplicationServices()->getDbProvider()
			->getNewStatementBuilder('AuthenticationListenerTest::setConsumerForTest');
		$fb = $isb->getFragmentBuilder();
		$isb->insert($fb->table($isb->getSqlMapping()->getOAuthApplicationTable()), $fb->column('application'),
			$fb->column('consumer_key'), $fb->column('consumer_secret'), $fb->column('timestamp_max_offset'),
			$fb->column('token_access_validity'), $fb->column('token_request_validity'), $fb->column('active'));
		$isb->addValues($fb->parameter('application'), $fb->parameter('consumer_key'), $fb->parameter('consumer_secret'),
			$fb->integerParameter('timestamp_max_offset'), $fb->parameter('token_access_validity'),
			$fb->parameter('token_request_validity'), $fb->booleanParameter('active'));
		$iq = $isb->insertQuery();
		$iq->bindParameter('application', 'Change_Test');
		$iq->bindParameter('consumer_key', 'ChangeTestConsumerKey');
		$iq->bindParameter('consumer_secret', 'ChangeTestConsumerSecret');
		$iq->bindParameter('timestamp_max_offset', 60);
		$iq->bindParameter('token_access_validity', 'P10Y');
		$iq->bindParameter('token_request_validity', 'P1D');
		$iq->bindParameter('active', true);
		$iq->execute();
	}

	/**
	 * @return array
	 * @throws \RuntimeException
	 */
	public function testOnRequestToken()
	{
		$this->setConsumerForTest();

		$controller = new \Change\Http\Rest\Controller($this->getApplication());
		$controller->setActionResolver(new \Change\Http\Rest\Resolver());

		$_SERVER['REQUEST_URI'] = '/rest.php/OAuth/RequestToken/';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$consumerSecret = 'ChangeTestConsumerSecret';
		$date = new \DateTime();
		$params = array(
			'oauth_callback' => 'http://localhost/admin.php/login?route=/',
			'oauth_consumer_key' => 'ChangeTestConsumerKey',
			'oauth_nonce' => \Change\Stdlib\String::random(8),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => $date->getTimestamp(),
			'oauth_version' => '1.0'
		);
		$utils = new \ZendOAuth\Http\Utility();
		$signature = $utils->sign($params, 'HMAC-SHA1', $consumerSecret, null, 'POST',
			'http://localhost:80/rest.php/OAuth/RequestToken/');
		$params['oauth_signature'] = $signature;
		$params['realm'] = 'Change_Test';

		foreach ($params as $oauthKey => $oauthParam)
		{
			$_POST[$oauthKey] = $oauthParam;
		}

		$request = new \Change\Http\Rest\Request();
		$event = new HttpEvent(null, $controller);
		$event->setRequest($request);
		$event->setParams($this->getDefaultEventArguments());

		$authenticationListener = new AuthenticationListener();
		$authenticationListener->onRequestToken($event);

		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$this->assertEquals(200, $result->getHttpStatusCode());
		$resultArray = $result->toArray();
		$this->assertArrayHasKey('oauth_token', $resultArray);
		$this->assertArrayHasKey('oauth_token_secret', $resultArray);
		$this->assertArrayHasKey('oauth_callback_confirmed', $resultArray);
		$this->assertTrue($resultArray['oauth_callback_confirmed']);

		return $resultArray;
	}

	/**
	 * @param array $oauthData
	 * @depends testOnRequestToken
	 * @return array
	 */
	public function testOnAuthorizeGet($oauthData)
	{
		$controller = new \Change\Http\Rest\Controller($this->getApplication());
		$controller->setActionResolver(new \Change\Http\Rest\Resolver());

		$_SERVER['REQUEST_URI'] = '/rest.php/OAuth/Authorize/';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['oauth_token'] = $oauthData['oauth_token'];

		$request = new \Change\Http\Rest\Request();
		$event = new HttpEvent(null, $controller);
		$event->setRequest($request);
		$event->setParams($this->getDefaultEventArguments());

		$authenticationListener = new AuthenticationListener();
		$authenticationListener->onAuthorize($event);

		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$this->assertEquals(200, $result->getHttpStatusCode());
		$resultArray = $result->toArray();
		$this->assertEquals($oauthData['oauth_token'], $resultArray['oauth_token']);
		$this->assertEquals('Change_Test', $resultArray['realm']);
		return array('result' => $resultArray, 'tokenSecret' => $oauthData['oauth_token_secret']);
	}

	/**
	 * Simulate the form submission of user login/password
	 * @param array $oauthData
	 * @depends testOnAuthorizeGet
	 * @return array
	 */
	public function testOnAuthorizePost($oauthData)
	{
		$controller = new \Change\Http\Rest\Controller($this->getApplication());
		$controller->setActionResolver(new \Change\Http\Rest\Resolver());

		$_SERVER['REQUEST_URI'] = '/rest.php/OAuth/Authorize/';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST = array_merge($_POST, $oauthData['result']);
		$_POST['login'] = 'test';
		$_POST['password'] = 'change';

		$callback = function (\Zend\EventManager\Event $event)
		{
			if ($event->getParam('login') === 'test'
				&& $event->getParam('password') == 'change'
				&& $event->getParam('realm') == 'Change_Test'
			)
			{
				$event->setParam('user', new  fakeUser_5498723());
			}
		};

		$authenticationManager = $this->getApplicationServices()->getAuthenticationManager();
		$this->assertInstanceOf('Change\User\AuthenticationManager', $authenticationManager);
		$toDetach = $authenticationManager->getEventManager()->attach(\Change\User\AuthenticationManager::EVENT_LOGIN, $callback);

		$request = new \Change\Http\Rest\Request();
		$event = new HttpEvent(null, $controller);
		$event->setRequest($request);
		$event->setParams($this->getDefaultEventArguments());

		$authenticationListener = new AuthenticationListener();
		$authenticationListener->onAuthorize($event);

		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$this->assertEquals(200, $result->getHttpStatusCode());
		$resultArray = $result->toArray();
		$this->assertEquals($oauthData['result']['oauth_token'], $resultArray['oauth_token']);
		$this->assertArrayHasKey('oauth_callback', $resultArray);
		$this->assertArrayHasKey('oauth_verifier', $resultArray);
		$this->assertNotNull($resultArray['oauth_verifier']);

		$authenticationManager->getEventManager()->detach($toDetach);
		$oauthData['result'] = $resultArray;
		return $oauthData;
	}

	/**
	 * @param $oauthData
	 * @depends testOnAuthorizePost
	 * @return array
	 */
	public function testOnAccessToken($oauthData)
	{
		$controller = new \Change\Http\Rest\Controller($this->getApplication());
		$controller->setActionResolver(new \Change\Http\Rest\Resolver());

		$_SERVER['REQUEST_URI'] = '/rest.php/OAuth/AccessToken/';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$consumerSecret = 'ChangeTestConsumerSecret';
		$date = new \DateTime();
		$params = array(
			'oauth_consumer_key' => 'ChangeTestConsumerKey',
			'oauth_token' => $oauthData['result']['oauth_token'],
			'oauth_verifier' => $oauthData['result']['oauth_verifier'],
			'oauth_nonce' => \Change\Stdlib\String::random(8),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => $date->getTimestamp(),
			'oauth_version' => '1.0'
		);
		$utils = new \ZendOAuth\Http\Utility();
		$signature = $utils->sign($params, 'HMAC-SHA1', $consumerSecret, $oauthData['tokenSecret'], 'POST',
			'http://localhost:80/rest.php/OAuth/AccessToken/');
		$params['oauth_signature'] = $signature;
		$params['realm'] = 'Change_Test';

		foreach ($params as $oauthKey => $oauthParam)
		{
			$_POST[$oauthKey] = $oauthParam;
		}

		$request = new \Change\Http\Rest\Request();
		$event = new HttpEvent(null, $controller);
		$event->setRequest($request);
		$event->setParams($this->getDefaultEventArguments());

		$authenticationListener = new AuthenticationListener();
		$authenticationListener->onAccessToken($event);

		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$this->assertEquals(200, $result->getHttpStatusCode());
		$resultArray = $result->toArray();
		$this->assertArrayHasKey('oauth_token', $resultArray);
		$this->assertNotEquals($oauthData['result']['oauth_token'], $resultArray['oauth_token']);
		$this->assertArrayHasKey('oauth_token_secret', $resultArray);
		$this->assertNotNull($resultArray['oauth_token_secret']);
	}
}

class fakeUser_5498723 extends \Change\User\AnonymousUser
{
	public function getId()
	{
		return 255;
	}

	public function authenticated()
	{
		return true;
	}
}
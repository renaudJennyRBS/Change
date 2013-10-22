<?php

use Change\Http\Event;
use Change\Http\Request;

class RevokeTokenTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Change\Http\OAuth\OAuthDbEntry
	 */
	protected $storedOAuth;

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
		$this->getApplicationServices()->getTransactionManager()->begin();

		//add a fake application
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->insert($qb->getSqlMapping()->getOAuthApplicationTable())
			->addColumns($fb->column('application'), $fb->column('consumer_key'), $fb->column('consumer_secret'))
			->addValues($fb->parameter('application'), $fb->parameter('consumer_key'), $fb->integerParameter('consumer_secret'));
		$iq = $qb->insertQuery();

		$iq->bindParameter('application', 'Rbs_Tests');
		$iq->bindParameter('consumer_key', 'consumerKeyForTests');
		$iq->bindParameter('consumer_secret', 'consumerSecretForTests');
		$iq->execute();

		//insert a fake token in database
		$this->storedOAuth = new \Change\Http\OAuth\OAuthDbEntry();
		$this->storedOAuth->setAccessorId(123456);
		$this->storedOAuth->setAuthorized(1);
		$this->storedOAuth->setRealm('Change_Tests');
		$this->storedOAuth->setToken('abcd123456789');
		$this->storedOAuth->setTokenSecret('TestTokenSecret');
		$this->storedOAuth->setType(\Change\Http\OAuth\OAuthDbEntry::TYPE_ACCESS);
		$this->storedOAuth->setCallback('oob');
		$this->storedOAuth->setCreationDate((new \DateTime())->sub(new \DateInterval('P5D')));
		$this->storedOAuth->setValidityDate((new \DateTime())->add(new \DateInterval('P10Y')));
		$this->storedOAuth->setConsumerKey('consumerKeyForTests');
		$oauth = new \Change\Http\OAuth\OAuthManager();
		$oauth->setApplicationServices($this->getApplicationServices());
		$oauth->insertToken($this->storedOAuth);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testExecute()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();

		//first check if token exist, use GetUserTokens to get it.
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('userId' => $this->storedOAuth->getAccessorId());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getUserTokens = new \Rbs\User\Http\Rest\Actions\GetUserTokens();
		$getUserTokens->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult, 'array result must contain the test token, if it is not, maybe because of GetUserTokens works wrong');
		$this->assertCount(1, $arrayResult);

		//Revoke and test again
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('token' => $this->storedOAuth->getToken());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$revokeToken = new \Rbs\User\Http\Rest\Actions\RevokeToken();
		$revokeToken->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('userId' => $this->storedOAuth->getAccessorId());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getUserTokens = new \Rbs\User\Http\Rest\Actions\GetUserTokens();
		$getUserTokens->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertEmpty($arrayResult);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}
<?php

use Change\Http\Event;
use Change\Http\Request;

class GetUserTokensTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Change\Http\OAuth\OAuthDbEntry
	 */
	protected $validStoredOAuth;

	/**
	 * @var \Change\Http\OAuth\OAuthDbEntry
	 */
	protected $invalidStoredOAuth;

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

		//insert fake tokens in database (AccessorId need to be the same for both)
		//a valid
		$this->validStoredOAuth = new \Change\Http\OAuth\OAuthDbEntry();
		$this->validStoredOAuth->setAccessorId(123456);
		$this->validStoredOAuth->setAuthorized(1);
		$this->validStoredOAuth->setRealm('Change_Tests');
		$this->validStoredOAuth->setToken('abcd123456789');
		$this->validStoredOAuth->setTokenSecret('TestTokenSecret');
		$this->validStoredOAuth->setType(\Change\Http\OAuth\OAuthDbEntry::TYPE_ACCESS);
		$this->validStoredOAuth->setCallback('oob');
		$this->validStoredOAuth->setCreationDate((new \DateTime())->sub(new \DateInterval('P5D')));
		$this->validStoredOAuth->setValidityDate((new \DateTime())->add(new \DateInterval('P10Y')));
		$this->validStoredOAuth->setConsumerKey('consumerKeyForTests');
		//an invalid
		$this->invalidStoredOAuth = new \Change\Http\OAuth\OAuthDbEntry();
		$this->invalidStoredOAuth->setAccessorId(123456);
		$this->invalidStoredOAuth->setAuthorized(1);
		$this->invalidStoredOAuth->setRealm('Change_Tests');
		$this->invalidStoredOAuth->setToken('dcba987654321');
		$this->invalidStoredOAuth->setTokenSecret('TestTokenSecret');
		$this->invalidStoredOAuth->setType(\Change\Http\OAuth\OAuthDbEntry::TYPE_ACCESS);
		$this->invalidStoredOAuth->setCallback('oob');
		$this->invalidStoredOAuth->setCreationDate((new \DateTime())->sub(new \DateInterval('P5D')));
		$this->invalidStoredOAuth->setValidityDate(new \DateTime());
		$this->invalidStoredOAuth->setConsumerKey('consumerKeyForTests');
		$oauth = new \Change\Http\OAuth\OAuthManager();
		$oauth->setApplicationServices($this->getApplicationServices());
		$oauth->insertToken($this->validStoredOAuth);
		$oauth->insertToken($this->invalidStoredOAuth);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testExecute()
	{
		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('userId' => $this->validStoredOAuth->getAccessorId());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$getUserTokens = new \Rbs\User\Http\Rest\Actions\GetUserTokens();
		$getUserTokens->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		$this->assertCount(1, $arrayResult, 'if it is 2, that mean that the invalid token is taken with');
		//Check token content
		$token = $arrayResult[0];
		$this->assertEquals($this->validStoredOAuth->getToken(), $token['token']);
		$this->assertEquals($this->validStoredOAuth->getRealm(), $token['realm']);
		$this->assertEquals('Rbs_Tests', $token['application']);
		$this->assertInternalType('string', $token['creation_date']);
		$this->assertEquals($this->validStoredOAuth->getCreationDate()->format(\DateTime::ISO8601), $token['creation_date']);
		$this->assertInternalType('string', $token['validity_date']);
		$this->assertEquals($this->validStoredOAuth->getValidityDate()->format(\DateTime::ISO8601), $token['validity_date']);
	}
}
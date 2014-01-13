<?php

namespace ChangeTests\Rbs\User\Http\Web;

/**
 * @name \ChangeTests\Rbs\User\Http\Web\CreateAccountRequestTest
 */
class CreateAccountRequestTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();
		$schema = new \Rbs\User\Setup\Schema($appServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$appServices->getDbProvider()->closeConnection();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testExecute()
	{
		$requestParams = new \Zend\Stdlib\Parameters([
			'email' => 'test@test.com',
			'password' => 'abcd123',
			'confirmpassword' => 'abcd123'
		]);

		$website = $this->getNewWebsite();
		$i18nManager = $this->getApplicationServices()->getI18nManager();
		$urlManager = $website->getUrlManager($i18nManager->getLCID());
		$urlManager->setAbsoluteUrl(true);

		$event = new \Change\Http\Web\Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setParam('website', $website);
		$event->setUrlManager($urlManager);
		$request = new \Change\Http\Request();
		$request->setMethod(\Zend\Http\Request::METHOD_POST);
		$request->setPost($requestParams);
		$request->setLCID($i18nManager->getLCID());
		$event->setRequest($request);

		//pre test
		//test if there is no job
		$jobManager = $this->getApplicationServices()->getJobManager();
		$this->assertEquals(0, $jobManager->getCountJobIds());

		$createAccountRequest = new \Rbs\User\Http\Web\CreateAccountRequest();
		$createAccountRequest->execute($event);

		$dbProvider = $this->getApplicationServices()->getDbProvider();
		$this->assertNotNull($event->getResult());
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$accountRequests = $this->getAccountRequestsFromEmail($requestParams->get('email'), $dbProvider);
		//there is only one request at this step.
		$this->assertCount(1, $accountRequests, 'there is more thant one account request in database');
		$accountRequest = $accountRequests[0];
		$this->assertEquals((new \DateTime())->getTimestamp(), $accountRequest['request_date']->getTimestamp(),
			'request date must be now (with 10s delta)', 10);
		//check if the job has been created
		$jobs = $jobManager->getJobIds();
		$this->assertCount(1, $jobs);
		$job = $jobManager->getJob($jobs[0]);
		$this->assertInstanceOf('\Change\Job\Job', $job);
		/* @var $job \Change\Job\Job */
		$this->assertEquals('Rbs_User_SendMail', $job->getName());
		$jobArgs = $job->getArguments();
		$this->assertArrayHasKey('email', $jobArgs);
		$this->assertEquals($requestParams->get('email'), $jobArgs['email']);
		$this->assertArrayHasKey('LCID', $jobArgs);
		$this->assertEquals($i18nManager->getLCID(), $jobArgs['LCID']);
		$this->assertArrayHasKey('params', $jobArgs);
		$this->assertNotEmpty($jobArgs['params']);
		$this->assertArrayHasKey('website', $jobArgs['params']);
		$this->assertEquals($website->getCurrentLocalization()->getTitle(), $jobArgs['params']['website']);
		$this->assertArrayHasKey('link', $jobArgs['params']);
		$query = ['requestId' => 1,'email' => $requestParams->get('email')];
		$expectedLink = $urlManager->getAjaxURL('Rbs_User', 'CreateAccountConfirmation', $query);
		$this->assertEquals($expectedLink, $jobArgs['params']['link']);
	}

	/**
	 * @param $email
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array
	 */
	protected function getAccountRequestsFromEmail($email, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('request_id'), $fb->column('email'), $fb->column('request_date'));
		$qb->from($fb->table('rbs_user_account_request'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('email'), $fb->parameter('email'))
		));
		$qb->orderDesc($fb->column('request_id'));
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		$sq->bindParameter('now', (new \DateTime()));
		return $sq->getResults($sq->getRowsConverter()
			->addIntCol('request_id')->addDtCol('request_date')->addStrCol('email'));
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws \Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('Test website');
		$website->setBaseurl('http://www.test.com');
		$website->getCurrentLocalization()->setTitle('Test website');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $website;
	}
}
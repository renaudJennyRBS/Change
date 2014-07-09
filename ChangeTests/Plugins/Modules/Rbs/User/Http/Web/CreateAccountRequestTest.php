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

	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::attachSharedListener($sharedEventManager);
		$this->attachGenericServicesSharedListener($sharedEventManager);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}


	public function testExecute()
	{
		// Register generic services.
		$genericServices = $this->genericServices;

		$requestParams = new \Zend\Stdlib\Parameters([
			'email' => 'test@test.com',
			'password' => 'abcd123',
			'confirmPassword' => 'abcd123'
		]);

		$website = $this->getNewWebsite();
		$this->getNewMail();
		$i18nManager = $this->getApplicationServices()->getI18nManager();
		$urlManager = $website->getUrlManager($i18nManager->getLCID());

		$event = new \Change\Http\Web\Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->getServices()->set('genericServices', $genericServices);
		$event->setParam('website', $website);
		$event->setUrlManager($urlManager);
		$request = new \Change\Http\Request();
		$request->setMethod(\Zend\Http\Request::METHOD_POST);
		$request->setPost($requestParams);
		$request->setLCID($i18nManager->getLCID());
		$event->setRequest($request);

		// Rre-test:
		// Test if there is no job.
		$jobManager = $this->getApplicationServices()->getJobManager();
		$this->assertEquals(0, $jobManager->getCountJobIds());

		$createAccountRequest = new \Rbs\User\Http\Web\CreateAccountRequest();
		$createAccountRequest->execute($event);

		$dbProvider = $this->getApplicationServices()->getDbProvider();
		$this->assertNotNull($event->getResult());
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		$accountRequests = $this->getAccountRequestsFromEmail($requestParams->get('email'), $dbProvider);
		// There is only one request at this step.
		$this->assertCount(1, $accountRequests, 'there is more thant one account request in database');
		$accountRequest = $accountRequests[0];
		$this->assertEquals((new \DateTime())->getTimestamp(), $accountRequest['request_date']->getTimestamp(),
			'request date must be now (with 10s delta)', 10);

		// Check if the job has been created.
		$jobs = $jobManager->getJobIds();
		$this->assertCount(1, $jobs);
		$job = $jobManager->getJob($jobs[0]);
		$this->assertInstanceOf('\Change\Job\Job', $job);
		/* @var $job \Change\Job\Job */
		$this->assertEquals('Rbs_Mail_SendMail', $job->getName());
		$jobArgs = $job->getArguments();
		$this->assertArrayHasKey('emails', $jobArgs);
		$this->assertArrayHasKey('to', $jobArgs['emails']);
		$this->assertEquals([$requestParams->get('email')], $jobArgs['emails']['to']);
		$this->assertArrayHasKey('LCID', $jobArgs);
		$this->assertEquals($i18nManager->getLCID(), $jobArgs['LCID']);
		$this->assertArrayHasKey('substitutions', $jobArgs);
		$this->assertNotEmpty($jobArgs['substitutions']);
		$this->assertArrayHasKey('website', $jobArgs['substitutions']);
		$this->assertEquals($website->getCurrentLocalization()->getTitle(), $jobArgs['substitutions']['website']);
		$this->assertArrayHasKey('link', $jobArgs['substitutions']);
		$query = ['requestId' => 1, 'email' => $requestParams->get('email')];
		$expectedLink = $urlManager->getAjaxURL('Rbs_User', 'CreateAccountConfirmation', $query);
		$this->assertEquals($expectedLink, $jobArgs['substitutions']['link']);
	}

	/**
	 * @param string $email
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
		$website = $this->getApplicationServices()->getDocumentManager()
			->getNewDocumentInstanceByModelName('Rbs_Website_Website');
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

	/**
	 * @return \Rbs\Mail\Documents\Mail
	 * @throws \Exception
	 */
	protected function getNewMail()
	{
		$mail = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Mail_Mail');
		/* @var $mail \Rbs\Mail\Documents\Mail */
		$mail->setCode('user_account_request');
		$mail->setLabel('test account request mail');
		$mail->getCurrentLocalization()->setSubject('test account request mail');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$mail->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $mail;
	}
}
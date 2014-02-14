<?php

namespace ChangeTests\Rbs\Commerce\Http\Web;

/**
 * @name \ChangeTests\Rbs\Commerce\Http\Web\CreateAccountRequestTest
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
		// Register generic services.
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('genericServices', $genericServices);

		$requestParams = new \Zend\Stdlib\Parameters([
			'email' => 'test@test.com',
			'password' => 'abcd123',
			'confirmPassword' => 'abcd123'
		]);

		$emailAddress = 'another-test@test.com';
		$transaction = $this->getNewTransaction('another-test@test.com');
		$queryParams = new \Zend\Stdlib\Parameters([
			'transactionId' => $transaction->getId()
		]);

		$website = $this->getNewWebsite();
		$this->getNewMail();
		$i18nManager = $this->getApplicationServices()->getI18nManager();
		$urlManager = $website->getUrlManager($i18nManager->getLCID());
		$urlManager->setPathRuleManager($this->getApplicationServices()->getPathRuleManager());
		$urlManager->setAbsoluteUrl(true);

		$event = new \Change\Http\Web\Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->getServices()->set('genericServices', $genericServices);
		$event->setParam('website', $website);
		$event->setUrlManager($urlManager);
		$request = new \Change\Http\Request();
		$request->setMethod(\Zend\Http\Request::METHOD_POST);
		$request->setQuery($queryParams);
		$request->setPost($requestParams);
		$request->setLCID($i18nManager->getLCID());
		$event->setRequest($request);

		// Pre-test:
		// Test if there is no job.
		$jobManager = $this->getApplicationServices()->getJobManager();
		$this->assertEquals(0, $jobManager->getCountJobIds());

		$createAccountRequest = new \Rbs\Payment\Http\Web\CreateAccountRequest();
		$createAccountRequest->execute($event);

		$dbProvider = $this->getApplicationServices()->getDbProvider();
		$this->assertNotNull($event->getResult());
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());

		// The email in the transaction overloads the one in the POST data.
		$accountRequests = $this->getAccountRequestsFromEmail($emailAddress, $dbProvider);
		// There is only one request at this step.
		$this->assertCount(1, $accountRequests, 'there is more thant one account request in database');
		$accountRequest = $accountRequests[0];
		$this->assertEquals((new \DateTime())->getTimestamp(), $accountRequest['request_date']->getTimestamp(),
			'request date must be now (with 10s delta)', 10);
		$configParameters = json_decode($accountRequest['config_parameters'], true);
		$this->assertArrayHasKey('Rbs_Commerce_TransactionId', $configParameters);
		$this->assertEquals($transaction->getId(), $configParameters['Rbs_Commerce_TransactionId']);

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
		$this->assertEquals([$emailAddress], $jobArgs['emails']['to']);
		$this->assertArrayHasKey('LCID', $jobArgs);
		$this->assertEquals($i18nManager->getLCID(), $jobArgs['LCID']);
		$this->assertArrayHasKey('substitutions', $jobArgs);
		$this->assertNotEmpty($jobArgs['substitutions']);
		$this->assertArrayHasKey('website', $jobArgs['substitutions']);
		$this->assertEquals($website->getCurrentLocalization()->getTitle(), $jobArgs['substitutions']['website']);
		$this->assertArrayHasKey('link', $jobArgs['substitutions']);
		$query = ['requestId' => 1, 'email' => $emailAddress];
		$expectedLink = $urlManager->getAjaxURL('Rbs_Payment', 'CreateAccountConfirmation', $query);
		$this->assertEquals($expectedLink, $jobArgs['substitutions']['link']);
	}

	/**
	 * @param string $email
	 * @throws \Exception
	 * @return \Rbs\Payment\Documents\Transaction
	 */
	protected function getNewTransaction($email)
	{
		$transaction = $this->getApplicationServices()->getDocumentManager()
			->getNewDocumentInstanceByModelName('Rbs_Payment_Transaction');
		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction->setEmail($email);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$transaction->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $transaction;
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
		$qb->select($fb->column('request_id'), $fb->column('email'), $fb->column('request_date'),
			$fb->column('config_parameters'));
		$qb->from($fb->table('rbs_user_account_request'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('email'), $fb->parameter('email'))
		));
		$qb->orderDesc($fb->column('request_id'));
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		$sq->bindParameter('now', (new \DateTime()));
		return $sq->getResults($sq->getRowsConverter()
			->addIntCol('request_id')->addDtCol('request_date')->addStrCol('email')->addStrCol('config_parameters'));
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
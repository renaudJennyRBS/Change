<?php

namespace ChangeTests\Rbs\User\Job;

/**
 * @name \ChangeTests\Rbs\User\Job\CleanAccountRequestTableTest
 */
class CleanAccountRequestTableTest extends \ChangeTests\Change\TestAssets\TestCase
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
		//declare a job manager listener for this test suit
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/JobManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\JobManager\\Listeners');

		//check if request table is empty
		$requests = $this->getAccountRequests();
		$this->assertCount(0, $requests);

		$firstRequestId = $this->insertNewAccountRequest();
		//check if request table is not empty
		$requests = $this->getAccountRequests();
		$this->assertCount(1, $requests);

		$jm =  $this->getApplicationServices()->getJobManager();
		$job = $jm->createNewJob('Rbs_User_CleanAccountRequestTable');
		$jm->run($job);
		//job is rescheduled so the status is waiting
		$this->assertEquals('waiting', $job->getStatus());

		//job hasn't clean anything because the request_date is not exceeded
		$requests = $this->getAccountRequests();
		$this->assertCount(1, $requests);

		//but now change the request date 25h before (that mean, the job has to clean the first entry)
		$this->updateAccountRequestDate($firstRequestId, (new \DateTime())->sub(new \DateInterval('PT25H')));
		//run the job again
		$jm->run($job);

		//now job has clean the job
		$requests = $this->getAccountRequests();
		$this->assertCount(0, $requests);
	}

	/**
	 * @param string $email
	 * @return int (request id)
	 * @throws \Exception
	 */
	protected function insertNewAccountRequest($email = 'test@test.com')
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dbProvider = $this->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->insert($fb->table('rbs_user_account_request'));
			$qb->addColumns($fb->column('email'), $fb->column('config_parameters'), $fb->column('request_date'));
			$qb->addValues($fb->parameter('email'), $fb->parameter('configParameters'), $fb->dateTimeParameter('requestDate'));
			$iq = $qb->insertQuery();

			$iq->bindParameter('email', $email);
			$iq->bindParameter('configParameters', json_encode([]));
			$iq->bindParameter('requestDate', new \DateTime());
			$iq->execute();

			$requestId = intval($dbProvider->getLastInsertId('rbs_user_account_request'));

			$tm->commit();

			return $requestId;
		}
		catch(\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @return array
	 */
	protected function getAccountRequests()
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('request_id'), $fb->column('request_date'));
		$qb->from($fb->table('rbs_user_account_request'));

		$qb->orderDesc($fb->column('request_id')); //define an order to get the last request
		$sq = $qb->query();

		return $sq->getResults($sq->getRowsConverter()->addIntCol('request_id')->addDtCol('request_date'));
	}

	/**
	 * @param integer $requestId
	 * @param \DateTime $newDate
	 * @throws \Exception
	 */
	protected function updateAccountRequestDate($requestId, $newDate)
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dbProvider = $this->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table('rbs_user_account_request'));
			$qb->assign($fb->column('request_date'), $fb->dateTimeParameter('requestDate'));
			$qb->where($fb->eq($fb->column('request_id'), $fb->integerParameter('requestId')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('requestId', $requestId);
			$uq->bindParameter('requestDate', $newDate);
			$uq->execute();

			$tm->commit();
		}
		catch(\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}
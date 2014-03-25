<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Job;

/**
 * @name \Change\Job\JobManager
 */
class JobManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'JobManager';
	const EVENT_PROCESS = 'process';

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider = null;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager = null;


	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}


	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/JobManager');
	}

	/**
	 * @api
	 * $step in \Change\Job\JobManager::EVENT_*
	 * @param string $step
	 * @param string $jobName
	 * @return string
	 */
	public static function composeEventName($step, $jobName)
	{
		return $step . '_' . $jobName;
	}

	/**
	 * @api
	 * @param JobInterface $job
	 */
	public function run(JobInterface $job = null)
	{
		if ($job === null || ($job->getStatus() !== JobInterface::STATUS_WAITING && $job->getStatus() !== JobInterface::STATUS_FAILED))
		{
			return;
		}

		$this->updateJobStatus($job, JobInterface::STATUS_RUNNING);

		try
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('job' => $job));
			$event = new Event(static::composeEventName(static::EVENT_PROCESS, $job->getName()), $this, $args);
			$this->getEventManager()->trigger($event);
			$status = $event->getParam('executionStatus', JobInterface::STATUS_SUCCESS);
			$arguments = $event->getParam('arguments', null);
		}
		catch (\Exception $e)
		{
			$status = JobInterface::STATUS_FAILED;
			$arguments = array('Exception' => array('code' => $e->getCode(), 'message' => $e->getMessage()));
		}

		$this->updateJobStatus($job, $status, $arguments);
	}

	/**
	 * @api
	 * @param $name
	 * @param array $argument
	 * @param \DateTime $startDate
	 * @param boolean $startTransaction
	 * @throws \Exception
	 * @return JobInterface
	 */
	public function createNewJob($name, array $argument = null, \DateTime $startDate = null, $startTransaction = true)
	{
		if ($startDate === null)
		{
			$startDate = new \DateTime();
		}

		$job = new Job();
		$job->setName($name)
			->setStartDate($startDate)
			->setStatus(JobInterface::STATUS_WAITING);

		if (is_array($argument) && count($argument))
		{
			$job->setArguments($argument);
		}
		else
		{
			$argument = array();
		}

		if ($startTransaction)
		{
			$transactionManager = $this->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$this->insertJob($job, $argument);

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
		else
		{
			$this->insertJob($job, $argument);
		}
		return $job;
	}

	/**
	 * @api
	 * @param JobInterface $job
	 * @param string $status
	 * @param array $arguments
	 * @param \DateTime $lastModificationDate
	 * @throws \Exception
	 */
	public function updateJobStatus($job, $status, array $arguments = null, \DateTime $lastModificationDate = null)
	{
		if ($job->getId() <= 0)
		{
			return;
		}

		if ($lastModificationDate === null)
		{
			$lastModificationDate = new \DateTime();
		}

		if (is_array($arguments) && count($arguments))
		{
			$arguments = array_merge($job->getArguments(), $arguments);
		}
		else
		{
			$arguments = null;
		}

		if ($status !== JobInterface::STATUS_RUNNING && $status !== JobInterface::STATUS_SUCCESS)
		{
			if ($status === JobInterface::STATUS_WAITING
				&& isset($arguments['reportedAt'])
				&& $arguments['reportedAt'] instanceof \DateTime
			)
			{
				$this->reportJob($job, $arguments);
				return;
			}

			$status = JobInterface::STATUS_FAILED;
		}

		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update('change_job');
			$qb->assign($fb->column('status'), $fb->parameter('status'));
			$qb->assign($fb->column('last_modification_date'), $fb->dateTimeParameter('lastModificationDate'));
			if ($arguments)
			{
				$qb->assign($fb->column('arguments'), $fb->lobParameter('arguments'));
			}
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));

			$uq = $qb->updateQuery();
			$uq->bindParameter('status', $status);
			$uq->bindParameter('lastModificationDate', $lastModificationDate);
			if ($arguments)
			{
				$uq->bindParameter('arguments', \Zend\Json\Json::encode($arguments));
			}

			$uq->bindParameter('id', $job->getId());
			$uq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		if ($job instanceof Job)
		{
			$job->setStatus($status);
			$job->setLastModificationDate($lastModificationDate);
			if ($arguments)
			{
				$job->setArguments($arguments);
			}
		}
	}

	/**
	 * @api
	 * @param JobInterface $job
	 * @param array $arguments
	 * @throws \Exception
	 */
	protected function reportJob($job, $arguments)
	{
		if ($job->getId() <= 0)
		{
			return;
		}

		$reportedAt = $arguments['reportedAt'];
		unset($arguments['reportedAt']);

		$lastModificationDate = new \DateTime();
		$status = JobInterface::STATUS_WAITING;

		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update('change_job');
			$qb->assign($fb->column('status'), $fb->parameter('status'));
			$qb->assign($fb->column('start_date'), $fb->dateTimeParameter('startDate'));
			$qb->assign($fb->column('last_modification_date'), $fb->dateTimeParameter('lastModificationDate'));
			$qb->assign($fb->column('arguments'), $fb->lobParameter('arguments'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));

			$uq = $qb->updateQuery();
			$uq->bindParameter('status', $status);
			$uq->bindParameter('startDate', $reportedAt);
			$uq->bindParameter('lastModificationDate', $lastModificationDate);
			$uq->bindParameter('arguments', \Zend\Json\Json::encode($arguments));
			$uq->bindParameter('id', $job->getId());
			$uq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		if ($job instanceof Job)
		{
			$job->setStatus($status);
			$job->setLastModificationDate($lastModificationDate);
			$job->setStartDate($reportedAt);
			$job->setArguments($arguments);
		}
	}

	/**
	 * @api
	 * @param integer $jobId
	 * @return JobInterface|null
	 */
	public function getJob($jobId)
	{
		$id = intval($jobId);
		if ($id > 0)
		{
			$qb = $this->getDbProvider()->getNewQueryBuilder('JobManager.getJob');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('name'), $fb->column('start_date'), $fb->column('arguments'),
					$fb->column('status'), $fb->column('last_modification_date'));
				$qb->from('change_job');
				$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			}

			$sq = $qb->query();
			$sq->bindParameter('id', $id);
			$rc = $sq->getRowsConverter();
			$rc->addStrCol('name', 'status')->addTxtCol('arguments')->addDtCol('start_date', 'last_modification_date');
			$data = $sq->getFirstResult($rc);
			if ($data)
			{
				$job = new Job();
				$job->setId($jobId);
				$job->setName($data['name']);
				$job->setStartDate($data['start_date']);
				if (isset($data['arguments']) && is_string($data['arguments']))
				{
					$job->setArguments(\Zend\Json\Json::decode($data['arguments'], \Zend\Json\Json::TYPE_ARRAY));
				}
				$job->setStatus($data['status']);
				$job->setLastModificationDate($data['last_modification_date']);
				return $job;
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param \DateTime $startDate
	 * @return integer[]
	 */
	public function getRunnableJobIds(\DateTime $startDate = null)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'))->from('change_job');
		$qb->where(
			$fb->logicAnd(
				$fb->eq($fb->column('status'), $fb->parameter('status')),
				$fb->lte($fb->column('start_date'), $fb->dateTimeParameter('startDate'))
			));
		$qb->orderAsc($fb->column('id'));
		$sq = $qb->query();
		$sq->bindParameter('status', JobInterface::STATUS_WAITING);
		$sq->bindParameter('startDate', $startDate ? $startDate : new \DateTime());
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id'));
	}

	/**
	 * @api
	 * @param string $status
	 * @return integer
	 */
	public function getCountJobIds($status = JobInterface::STATUS_WAITING)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->func('count', $fb->column('id')), 'count'))
			->from('change_job');
		$qb->where($fb->eq($fb->column('status'), $fb->parameter('status')));
		$sq = $qb->query();
		$sq->bindParameter('status', $status);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('count'));
	}

	/**
	 * @api
	 * @param string $status
	 * @param int $offset
	 * @param int $limit
	 * @return integer[]
	 */
	public function getJobIds($status = JobInterface::STATUS_WAITING, $offset = 0, $limit = 20)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'))->from('change_job');
		$qb->where($fb->eq($fb->column('status'), $fb->parameter('status')));
		$qb->orderDesc($fb->column('start_date'));
		$sq = $qb->query();
		$sq->bindParameter('status', $status);
		$sq->setStartIndex($offset);
		$sq->setMaxResults($limit);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id'));
	}

	/**
	 * @api
	 * @param string $name
	 * @param integer $offset
	 * @param integer $limit
	 * @return integer[]
	 */
	public function getJobIdsByName($name, $offset = 0, $limit = 20)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'))->from('change_job');
		$qb->where($fb->eq($fb->column('name'), $fb->parameter('name')));
		$qb->orderDesc($fb->column('start_date'));
		$sq = $qb->query();
		$sq->bindParameter('name', $name);
		$sq->setStartIndex($offset);
		$sq->setMaxResults($limit);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id'));
	}

	/**
	 * @api
	 * @param JobInterface $job
	 * @throws \Exception
	 */
	public function deleteJob(JobInterface $job)
	{
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getDbProvider()->getNewStatementBuilder('JobManager.deleteJob');
			$fb = $qb->getFragmentBuilder();
			$qb->delete('change_job');
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			$dq = $qb->deleteQuery();
			$dq->bindParameter('id', $job->getId());
			$dq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param Job $job
	 * @param array $argument
	 */
	protected function insertJob($job, array $argument)
	{
		$argumentJSON = ($argument !== null) ? \Zend\Json\Json::encode($argument) : null;
		$qb = $this->getDbProvider()->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->insert('change_job', $fb->column('name'),
			$fb->column('start_date'),
			$fb->column('arguments'),
			$fb->column('status'));

		$qb->addValues($fb->parameter('name'),
			$fb->dateTimeParameter('startDate'),
			$fb->lobParameter('arguments'),
			$fb->parameter('status'));
		$iq = $qb->insertQuery();

		$iq->bindParameter('name', $job->getName());
		$iq->bindParameter('startDate', $job->getStartDate());
		$iq->bindParameter('arguments', $argumentJSON);
		$iq->bindParameter('status', $job->getStatus());
		$iq->execute();
		$job->setId(intval($iq->getDbProvider()->getLastInsertId('change_job')));
		$this->getLogging()->info('New Job: ' . $job->getName() . ', ' . $job->getId() . ', ' . $argumentJSON);
	}
}
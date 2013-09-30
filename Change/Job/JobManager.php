<?php
namespace Change\Job;

use Change\Documents\DocumentServices;
use Change\Application\ApplicationServices;

/**
 * @name \Change\Job\JobManager
 */
class JobManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'JobManager';

	const EVENT_PROCESS = 'process';

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @throws \RuntimeException
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		if ($this->applicationServices === null)
		{
			throw new \RuntimeException('ApplicationServices not set', 999999);
		}
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
		if ($documentServices && $this->applicationServices === null)
		{
			$this->setApplicationServices($documentServices->getApplicationServices());
		}
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		if ($this->documentServices === null)
		{
			$this->documentServices = new DocumentServices($this->getApplicationServices());
		}
		return $this->documentServices;
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
		$config = $this->getApplicationServices()->getApplication()->getConfiguration();
		$classNames =  $config->getEntry('Change/Events/JobManager');
		return is_array($classNames) ? $classNames : array();
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
		if ($job === null || $job->getStatus() !== JobInterface::STATUS_WAITING)
		{
			return;
		}

		$this->updateJobStatus($job, JobInterface::STATUS_RUNNING);

		try
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('job' => $job, 'documentServices' => $this->getDocumentServices()));
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
	 * @throws \Exception
	 * @return JobInterface
	 */
	public function createNewJob($name, array $argument = null, \DateTime $startDate = null)
	{
		if ($startDate === null)
		{
			$startDate = new \DateTime();
		}

		$transactionManager = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
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
				$argument = null;
			}

			$argumentJSON = ($argument !== null) ? \Zend\Json\Json::encode($argument) : null;
			$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
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
			$this->applicationServices->getLogging()->info('New Job: ' . $job->getName(). ', ' . $job->getId() . ', '. $argumentJSON);
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
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
				&& isset($arguments['reportedAt']) && $arguments['reportedAt'] instanceof \DateTime)
			{
				$this->reportJob($job, $arguments);
				return;
			}

			$status =  JobInterface::STATUS_FAILED;
		}

		$transactionManager = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update('change_job');
			$qb->assign($fb->column('status'), $fb->parameter('status'));
			$qb->assign($fb->column('last_modification_date'), $fb->dateTimeParameter('lastModificationDate'));
			if ($arguments)
			{
				$qb->assign($fb->column('arguments'), $fb->lobParameter('arguments'));
			}
			$qb->where($fb->eq($fb->column('id'),$fb->integerParameter('id')));

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
		$status =  JobInterface::STATUS_WAITING;

		$transactionManager = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update('change_job');
			$qb->assign($fb->column('status'), $fb->parameter('status'));
			$qb->assign($fb->column('start_date'), $fb->dateTimeParameter('startDate'));
			$qb->assign($fb->column('last_modification_date'), $fb->dateTimeParameter('lastModificationDate'));
			$qb->assign($fb->column('arguments'), $fb->lobParameter('arguments'));
			$qb->where($fb->eq($fb->column('id'),$fb->integerParameter('id')));

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
			$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder('JobManager.getJob');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('name'), $fb->column('start_date'), $fb->column('arguments'),
					$fb->column('status'), $fb->column('last_modification_date'));
				$qb->from('change_job');
				$qb->where($fb->eq($fb->column('id'),$fb->integerParameter('id')));
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'))->from('change_job');
		$qb->where(
			$fb->logicAnd(
				$fb->eq($fb->column('status'),$fb->parameter('status')),
				$fb->lte($fb->column('start_date'),$fb->dateTimeParameter('startDate'))
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->func('count', $fb->column('id')), 'count'))
			->from('change_job');
		$qb->where($fb->eq($fb->column('status'),$fb->parameter('status')));
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'))->from('change_job');
		$qb->where($fb->eq($fb->column('status'),$fb->parameter('status')));
		$qb->orderDesc($fb->column('start_date'));
		$sq = $qb->query();
		$sq->bindParameter('status', $status);
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
		$transactionManager = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('JobManager.deleteJob');
			$fb = $qb->getFragmentBuilder();
			$qb->delete('change_job');
			$qb->where($fb->eq($fb->column('id'),$fb->integerParameter('id')));
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
}
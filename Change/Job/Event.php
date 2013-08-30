<?php
namespace Change\Job;

use Change\Documents\DocumentServices;
use Change\Application\ApplicationServices;

/**
* @name \Change\Job\Event
*/
class Event extends \Zend\EventManager\Event
{
	/**
	 * @return JobManager
	 */
	public function getJobManager()
	{
		return $this->getTarget();
	}

	/**
	 * @return JobInterface
	 */
	public function getJob()
	{
		return $this->getParam('job');
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->getParam('documentServices');
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->getJobManager()->getApplicationServices();
	}

	public function success()
	{
		$this->setParam('executionStatus', JobInterface::STATUS_SUCCESS);
	}

	public function reported(\DateTime $reportedAt)
	{
		$this->setParam('executionStatus', JobInterface::STATUS_WAITING);

		$arguments = $this->getParam('arguments');
		if (!is_array($arguments))
		{
			$arguments = array();
		}
		$arguments['reportedAt'] = $reportedAt;
		$this->setParam('arguments', $arguments);
	}

	public function failed($error)
	{
		if ($error)
		{
			$arguments = $this->getParam('arguments');
			if (!is_array($arguments))
			{
				$arguments = array();
			}
			if (!isset($arguments['error']) || !is_array($arguments['error']))
			{
				$arguments['error'] = array();
			}
			$arguments['error'][] = $error;

			$this->setParam('arguments', $arguments);
		}

		$this->setParam('executionStatus', JobInterface::STATUS_FAILED);
	}
}
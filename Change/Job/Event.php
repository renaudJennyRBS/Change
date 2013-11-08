<?php
namespace Change\Job;

/**
* @name \Change\Job\Event
*/
class Event extends \Change\Events\Event
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
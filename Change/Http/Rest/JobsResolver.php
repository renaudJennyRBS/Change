<?php
namespace Change\Http\Rest;

use Change\Http\Event;
use Change\Job;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\Link;
use Change\Http\Rest\Result\JobResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\JobsResolver
 */
class JobsResolver
{
	const RESOLVER_NAME = 'jobs';

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}



	/**
	 * @param Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array(Job\JobInterface::STATUS_WAITING, Job\JobInterface::STATUS_RUNNING,
			Job\JobInterface::STATUS_SUCCESS, Job\JobInterface::STATUS_FAILED);
	}

	/**
	 * Set Event params: resourcesActionName, documentId, LCID
	 * @param Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		if ($nbParts == 0 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, static::RESOLVER_NAME);
			$event->setParam('namespace', implode('.', $resourceParts));
			$event->setParam('resolver', $this);
			$action = function ($event)
			{
				$action = new DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($nbParts == 1 && $method === Request::METHOD_GET)
		{
			if ($event->getParam('isDirectory'))
			{
				$status = $resourceParts[0];
				if (in_array($status, $this->getNextNamespace($event, array())))
				{
					$event->setParam('status', $status);
					$event->setAction(array($this, 'getJobCollection'));
				}
			}
			else
			{
				$jobId = $resourceParts[0];
				if (is_numeric($jobId))
				{
					$event->setParam('jobId', intval($jobId));
					$event->setAction(array($this, 'getJob'));
				}
			}
			return;
		}
		elseif ($nbParts == 2 && $method === Request::METHOD_GET)
		{
			$jobId = $resourceParts[0];
			$run = $resourceParts[1];
			if (is_numeric($jobId) && $run === 'run')
			{
				$event->setParam('jobId', intval($jobId));
				$event->setAction(array($this, 'runJob'));
			}
			return;
		}
		elseif ($nbParts == 1 && $method === Request::METHOD_DELETE)
		{
			$jobId = $resourceParts[0];
			if (is_numeric($jobId))
			{
				$event->setParam('jobId', intval($jobId));
				$event->setAction(array($this, 'deleteJob'));
			}
			return;
		}
	}

	public function runJob(Event $event)
	{
		$jobId = $event->getParam('jobId');
		$jobManager = new Job\JobManager();
		$jobManager->setApplicationServices($event->getApplicationServices());
		$jobManager->setDocumentServices($event->getDocumentServices());
		$job  = $jobManager->getJob($jobId);
		if ($job)
		{
			if ($job->getStatus() === Job\JobInterface::STATUS_WAITING)
			{
				$jobManager->run($job);
				$this->getJob($event);
			}
			else
			{
				$errorCode = 999999;
				$errorMessage = 'Invalid job status "' . $job->getStatus(). '", "' .Job\JobInterface::STATUS_WAITING . '" expected';
				$httpStatusCode = HttpResponse::STATUS_CODE_409;
				$result = new \Change\Http\Rest\Result\ErrorResult($errorCode, $errorMessage, $httpStatusCode);
				$event->setResult($result);
			}
		}
	}

	public function deleteJob(Event $event)
	{
		$jobId = $event->getParam('jobId');
		$jobManager = new Job\JobManager();
		$jobManager->setApplicationServices($event->getApplicationServices());
		$jobManager->setDocumentServices($event->getDocumentServices());
		$job = $jobManager->getJob($jobId);

		if ($job)
		{
			if ($job->getStatus() === Job\JobInterface::STATUS_SUCCESS || $job->getStatus() === Job\JobInterface::STATUS_FAILED)
			{
				$jobManager->deleteJob($job);
				$job = $jobManager->getJob($jobId);
			}
			else
			{
				$errorCode = 999999;
				$errorMessage = 'Invalid job status "' . $job->getStatus(). '", "' .
					Job\JobInterface::STATUS_SUCCESS . '" or "' .
					Job\JobInterface::STATUS_FAILED . '" expected';
				$httpStatusCode = HttpResponse::STATUS_CODE_409;
				$result = new \Change\Http\Rest\Result\ErrorResult($errorCode, $errorMessage, $httpStatusCode);
				$event->setResult($result);
				return;
			}
		}

		if (!$job)
		{
			$result = new \Change\Http\Result();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_204);
			$event->setResult($result);
		}
	}

	public function getJob(Event $event)
	{
		$urlManager = $event->getUrlManager();
		$jobId = $event->getParam('jobId');
		$jobManager = new Job\JobManager();
		$jobManager->setApplicationServices($event->getApplicationServices());
		$jobManager->setDocumentServices($event->getDocumentServices());
		$job  = $jobManager->getJob($jobId);

		if ($job)
		{
			$result = new JobResult($urlManager);
			$result->addLink(new Link($urlManager, static::RESOLVER_NAME . '/' . $job->getId()));
			$result->setProperty('id', $job->getId());
			$result->setProperty('name', $job->getName());
			$result->setProperty('startDate', $job->getStartDate()->format(\DateTime::ISO8601));
			$result->setProperty('status', $job->getStatus());
			$lastModDate = $job->getLastModificationDate();
			if ($lastModDate)
			{
				$result->setProperty('lastModificationDate', $lastModDate->format(\DateTime::ISO8601));
			}
			$result->setProperty('arguments', $job->getArguments());

			if ($job->getStatus() === Job\JobInterface::STATUS_WAITING)
			{
				$result->addLink(new Link($urlManager, static::RESOLVER_NAME . '/' . $job->getId(). '/run', 'run'));
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}

	public function getJobCollection(Event $event)
	{
		$status = $event->getParam('status');
		$jobManager = new Job\JobManager();
		$jobManager->setApplicationServices($event->getApplicationServices());
		$jobManager->setDocumentServices($event->getDocumentServices());

		$count = $jobManager->getCountJobIds($status);

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$result->setCount($count);
		$result->setSort('startDate');
		$result->setDesc(true);

		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		if ($count)
		{
			$ids = $jobManager->getJobIds($status, $result->getOffset(), $result->getLimit());
			foreach ($ids as $id)
			{
				$job  = $jobManager->getJob($id);
				if ($job)
				{
					$row = new JobResult($urlManager);
					$row->addLink(new Link($urlManager, static::RESOLVER_NAME . '/' . $job->getId()));
					$row->setProperty('id', $job->getId());
					$row->setProperty('name', $job->getName());
					$row->setProperty('startDate', $job->getStartDate()->format(\DateTime::ISO8601));
					$row->setProperty('status', $job->getStatus());
					$result->addResource($row);
				}
			}
		}
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
	}

	/**
	 * @param CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		$array = array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
		if ($result->getSort())
		{
			$array['sort'] = $result->getSort();
			$array['desc'] = ($result->getDesc()) ? 'true' : 'false';
		}
		return $array;
	}
}
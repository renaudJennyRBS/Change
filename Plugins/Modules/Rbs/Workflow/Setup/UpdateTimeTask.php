<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class UpdateTimeTask
{
	public function migrate(\Change\Events\Event $event)
	{

		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();

		try {
			$tm->begin();

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();

			$job = $fb->table('change_job');
			$qb->select($fb->alias($fb->func('COUNT')->addArgument($fb->column('id')), 'jobCount'))
				->from($job)
				->where($fb->eq($fb->column('name'), $fb->string('Rbs_Workflow_ExecuteDeadLineTask')));

			$query = $qb->query();

			$jobCount = $query->getFirstResult($query->getRowsConverter()->addIntCol('jobCount')->singleColumn('jobCount'));
			if ($jobCount)
			{
				echo 'Jobs to delete: ', $jobCount, PHP_EOL;
				$stb = $dbProvider->getNewStatementBuilder();
				$fb = $stb->getFragmentBuilder();
				$stb->delete($job)->where($fb->eq($fb->column('name'), $fb->string('Rbs_Workflow_ExecuteDeadLineTask')));

				$delete = $stb->deleteQuery();

				$deletedJobs = $delete->execute();
				echo 'Deleted jobs: ', $deletedJobs, PHP_EOL;

				$task = $fb->table('rbs_workflow_doc_task');
				$stb = $dbProvider->getNewStatementBuilder();
				$fb = $stb->getFragmentBuilder();
				$stb->update($task)
					->assign($fb->column('deadline'), $fb->string(null))
					->where($fb->isNotNull($fb->column('deadline')));
				$update = $stb->updateQuery();
				$updatedTasks = $update->execute();
				echo 'Updated tasks: ', $updatedTasks, PHP_EOL;
			}
			else
			{
				echo 'no job to delete.', PHP_EOL;
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$event->getApplication()->getLogging()->exception($e);
			echo $e->getMessage(), PHP_EOL;
			throw $tm->rollBack($e);
		}
	}
}

$eventManager = $application->getNewEventManager('TimeTask');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new UpdateTimeTask())->migrate($event);
});

$eventManager->trigger('migrate', null, []);
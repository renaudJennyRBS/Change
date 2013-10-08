<?php
namespace Rbs\Generic\Events\JobManager;

use Change\Job\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\JobManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\ExecuteDeadLineTask())->execute($event);
		};
		$events->attach('process_Rbs_Workflow_ExecuteDeadLineTask', $callback, 5);

		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\DocumentCleanUp())->cleanUp($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callback, 5);

		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\DocumentCleanUp())->localizedCleanUp($event);
		};
		$events->attach('process_Change_Document_LocalizedCleanUp', $callback, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Timeline\Job\SendTemplateMail())->execute($event);
		};
		$events->attach('process_Rbs_Timeline_SendTemplateMail', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Notification\Job\SendMails())->execute($event);
		};
		$events->attach('process_Rbs_Notification_SendMails', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Seo\Job\GenerateSitemap())->execute($event);
		};
		$events->attach('process_Rbs_Seo_GenerateSitemap', $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
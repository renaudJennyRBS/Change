<?php
namespace ChangeTests\Change\Workflow;

use Change\Workflow\WorkflowManager;

/**
 * @name \ChangeTests\Change\Collection\WorkflowManagerTest
 */
class WorkflowManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return WorkflowManager
	 */
	protected function getWorkflowManager()
	{
		return $this->getApplicationServices()->getWorkflowManager();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('Change\Workflow\WorkflowManager', $this->getWorkflowManager());
	}

	public function testGetWorkflow()
	{
		$wm = $this->getWorkflowManager();
		$dt = new \DateTime('2013-06-07 00:00:00');
		$this->assertNull($wm->getWorkflow('not_found', $dt));

		$callback = function(\Change\Events\Event $event) use ($dt){
			if ($event->getParam('startTask') === 'not_found' && $event->getParam('date') == $dt)
			{
				$event->setParam('workflow' , new TestAssets\Workflow());
			}
		};

		$toDetach = $wm->getEventManager()->attach(WorkflowManager::EVENT_EXAMINE, $callback);
		$w = $wm->getWorkflow('not_found', $dt);
		$this->assertInstanceOf('ChangeTests\Change\Workflow\TestAssets\Workflow', $w);

		$wm->getEventManager()->detach($toDetach);
		$this->assertNull($wm->getWorkflow('not_found', $dt));
	}

	public function testGetNewWorkflowInstance()
	{
		$wm = $this->getWorkflowManager();
		$dt = new \DateTime('2013-06-07 00:00:00');
		$callback = function(\Change\Events\Event $event) use ($dt){
			if ($event->getParam('startTask') === 'GetNewWorkflowInstance' && $event->getParam('date') == $dt)
			{
				$workflow = new TestAssets\Workflow();
				$workflow->initMinimalValid();
				$event->setParam('workflow', $workflow);
			}
		};

		$toDetach = $wm->getEventManager()->attach(WorkflowManager::EVENT_EXAMINE, $callback);

		/* @var $wi \ChangeTests\Change\Workflow\TestAssets\WorkflowInstance */
		$wi = $wm->getNewWorkflowInstance('GetNewWorkflowInstance', array(TestAssets\WorkItem::DATE_CONTEXT_KEY => $dt, 'test1' => 'val1'));
		$this->assertInstanceOf('ChangeTests\Change\Workflow\TestAssets\WorkflowInstance', $wi);
		$this->assertEquals($wi->getStartDate(), $dt);
		$this->assertEquals($wi->context['test1'], 'val1');

		$wm->getEventManager()->detach($toDetach);
	}

	public function testProcessWorkflowInstance()
	{
		$wm = $this->getWorkflowManager();
		$dt = new \DateTime('2013-06-07 00:00:00');
		$callback = function(\Change\Events\Event $event) use ($dt) {
			if ($event->getParam('taskId') === 127)
			{
				$workflowInstance = new TestAssets\WorkflowInstance(new TestAssets\Workflow());
				$event->setParam('workflowInstance', $workflowInstance);
				$event->setParam('taskId', 721);
			}
		};

		$toDetach = $wm->getEventManager()->attach(WorkflowManager::EVENT_PROCESS, $callback);

		/* @var $wi \ChangeTests\Change\Workflow\TestAssets\WorkflowInstance */
		$wi = $wm->processWorkflowInstance(127, array(TestAssets\WorkItem::DATE_CONTEXT_KEY => $dt, 'test2' => 'val2'));
		$this->assertInstanceOf('ChangeTests\Change\Workflow\TestAssets\WorkflowInstance', $wi);
		$this->assertEquals($wi->execute, 721);
		$this->assertEquals($wi->context['test2'], 'val2');
		$wm->getEventManager()->detach($toDetach);
	}
}
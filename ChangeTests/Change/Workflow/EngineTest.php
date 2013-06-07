<?php
namespace ChangeTests\Change\Workflow;

use Change\Workflow\Engine;
use ChangeTests\Change\Workflow\TestAssets\Workflow;
use ChangeTests\Change\Workflow\TestAssets\WorkflowInstance;

/**
* @name \ChangeTests\Change\Workflow\EngineTest
*/
class EngineTest extends \ChangeTests\Change\TestAssets\TestCase
{

	/**
	 * @return $o
	 */
	protected function getObject($dt = null)
	{
		return new Engine(new WorkflowInstance(new Workflow()), $dt);
	}

	public function testConstruct()
	{
		$dt = new \DateTime('2013-06-07 00:00:00');
		$o = $this->getObject($dt);

		$this->assertInstanceOf('\ChangeTests\Change\Workflow\TestAssets\WorkflowInstance', $o->getWorkflowInstance());
		$this->assertEquals($dt, $o->getDateTime());

/*
		try
		{
			$o->getStartPlace();
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid Workflow type', $e->getMessage());
		}
*/
	}

	public function testGetStartPlace()
	{
		$o = $this->getObject();
		$this->assertNull($o->getStartPlace());

		/* @var $w TestAssets\Workflow */
		$w = $o->getWorkflowInstance()->getWorkflow();
		$w->initMinimalValid();

		$p = $o->getStartPlace();
		$this->assertInstanceOf('\ChangeTests\Change\Workflow\TestAssets\Place', $p);
		$this->assertEquals(1, $p->getId());
	}

	public function testEnableToken()
	{
		$o = $this->getObject();
		/* @var $i TestAssets\WorkflowInstance */
		$i = $o->getWorkflowInstance();
		$w = $i->getWorkflow();
		$w->initMinimalValid();

		/* @var $p TestAssets\Place */
		$p = $o->getStartPlace();

		try
		{
			$p->workflow = null;
			$o->enableToken($p);

			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid Place Workflow', $e->getMessage());
			$p->workflow = $w;
		}

		$t = $o->enableToken($p);
		$this->assertInstanceOf('\ChangeTests\Change\Workflow\TestAssets\Token', $t);
		$this->assertEquals(TestAssets\WorkflowInstance::STATUS_CLOSED, $i->getStatus());
		$this->assertInstanceOf('\DateTime', $i->getEndDate());
	}

	public function testGetEnabledWorkItemByTaskId()
	{
		$o = $this->getObject();
		/* @var $i TestAssets\WorkflowInstance */
		$i = $o->getWorkflowInstance();
		$w = $i->getWorkflow();
		$w->initMinimalValid();

		/* @var $p TestAssets\Place */
		$p = $o->getStartPlace();

		/* @var $transition TestAssets\Transition */
		$transition = $w->getItemById(3);
		$transition->trigger = TestAssets\Transition::TRIGGER_MSG;

		$o->enableToken($p);
		$t = $i->item[0];
		$this->assertEquals(TestAssets\Token::STATUS_FREE, $t->getStatus());

		$wi = $i->item[1];
		$this->assertEquals(TestAssets\WorkItem::STATUS_ENABLED, $wi->getStatus());
		$id = $wi->getTaskId();
		$this->assertEquals(1, $id);

		$this->assertNull($o->getEnabledWorkItemByTaskId(-1));
		$this->assertSame($wi, $o->getEnabledWorkItemByTaskId(1));
		$this->assertInstanceOf('\DateTime', $wi->getEnabledDate());
		$this->assertNull($wi->getCanceledDate());
		$this->assertNull($wi->getFinishedDate());
	}

	public function testFiredWorkItem()
	{
		$o = $this->getObject();
		/* @var $i TestAssets\WorkflowInstance */
		$i = $o->getWorkflowInstance();
		$w = $i->getWorkflow();
		$w->initMinimalValid();

		/* @var $p TestAssets\Place */
		$p = $o->getStartPlace();

		/* @var $transition TestAssets\Transition */
		$transition = $w->getItemById(3);
		$transition->trigger = TestAssets\Transition::TRIGGER_MSG;

		$o->enableToken($p);
		$wi = $o->getEnabledWorkItemByTaskId(1);
		$this->assertEquals(TestAssets\WorkItem::STATUS_ENABLED, $wi->getStatus());

		$o->firedWorkItem($wi);
		$this->assertCount(3, $i->item);
		$to = $i->item[0];
		$this->assertEquals(TestAssets\Token::STATUS_CONSUMED, $to->getStatus());
		$this->assertInstanceOf('\DateTime', $to->getConsumedDate());
		$this->assertSame($wi, $i->execute[0]);

		$this->assertEquals(TestAssets\WorkItem::STATUS_FINISHED, $wi->getStatus());
		$this->assertInstanceOf('\DateTime', $wi->getFinishedDate());
		$this->assertEquals(TestAssets\WorkflowInstance::STATUS_CLOSED, $i->getStatus());
		$this->assertInstanceOf('\DateTime', $i->getEndDate());

		$to = $i->item[2];
		$this->assertEquals(TestAssets\Token::STATUS_CONSUMED, $to->getStatus());
		$this->assertInstanceOf('\DateTime', $to->getConsumedDate());
		$this->assertSame($wi, $i->execute[0]);
	}
}
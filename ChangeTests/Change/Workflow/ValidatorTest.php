<?php
namespace ChangeTests\Change\Workflow;

use Change\Workflow\Validator;


/**
* @name \ChangeTests\Change\Workflow\ValidatorTest
*/
class ValidatorTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return Validator
	 */
	protected function getObject()
	{
		return new Validator();
	}

	public function testIsValidArgument()
	{
		$v = $this->getObject();
		try
		{
			$v->isValid(null);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid Workflow type', $e->getMessage());
		}

		$workflow = new TestAssets\Workflow();
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Empty Workflow name', $e->getMessage());
		}

		$workflow->name = 'test';
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Empty Workflow start task: test', $e->getMessage());
		}

		$workflow->startTask = 'start';
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid items array size', $e->getMessage());
		}

		$workflow->items = array('A','A','A','A');
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid items array size', $e->getMessage());
		}

		$workflow->items[] = 'A';
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid Workflow item at index: 0', $e->getMessage());
		}
	}

	public function testIsValidMinimal()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initMinimalValid();
		$this->assertTrue($v->isValid($workflow));
	}

	public function testWorkflowItem()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initMinimalValid();
		$p1 = $workflow->items[0];

		try
		{

			$p1->id = 0;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Empty item Id at index: 0', $e->getMessage());
		}

		try
		{
			$p1->id = 2;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Duplicate item Id at index: 1, id: 2', $e->getMessage());
		}

		try
		{
			$p1->id = 1;
			$p1->workflow = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid item Workflow: 1', $e->getMessage());
		}
	}

	public function testPlace()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initMinimalValid();
		$p1 = $workflow->items[0];
		try
		{
			$p1->name = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Invalid Place name: 1', $e->getMessage());
			$p1->name = 'start';
		}

		$p2 = $workflow->items[1];
		try
		{
			$p2->type = TestAssets\Place::TYPE_START;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Duplicate Start Place', $e->getMessage());
			$p2->type = TestAssets\Place::TYPE_END;
		}

		try
		{
			$p1->type = TestAssets\Place::TYPE_END;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Output Arc End Place', $e->getMessage());
			$p1->type = TestAssets\Place::TYPE_START;
		}

		try
		{
			$p1->type = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Place type', $e->getMessage());
			$p1->type = TestAssets\Place::TYPE_START;
		}

		try
		{
			$p1->arcs = array();
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Place with no output Arc', $e->getMessage());
			$p1->arcs[] = $workflow->items[3];
		}

		try
		{
			$p2->arcs = array();
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Place with no input Arc', $e->getMessage());
			$p2->arcs[] = $workflow->items[4];
		}

		$workflow->items = array_reverse($workflow->items);
		try
		{
			$p1->type = TestAssets\Place::TYPE_END;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Duplicate End Place', $e->getMessage());
			$p1->type = TestAssets\Place::TYPE_START;
		}
	}

	public function testTransition()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initMinimalValid();
		/* @var $t3 TestAssets\Transition */
		$t3 = $workflow->items[2];
		try
		{
			$t3->name = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Transition name', $e->getMessage());
			$t3->name = 'auto';
		}

		try
		{
			$t3->taskCode = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Task Code', $e->getMessage());
			$t3->taskCode = 'task_auto';
		}

		try
		{
			$t3->trigger = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Transition trigger', $e->getMessage());
			$t3->trigger = TestAssets\Transition::TRIGGER_AUTO;
		}

		try
		{
			$t3->trigger = TestAssets\Transition::TRIGGER_USER;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Transition User Role trigger', $e->getMessage());
			$t3->trigger = TestAssets\Transition::TRIGGER_AUTO;
		}

		try
		{
			$t3->trigger = TestAssets\Transition::TRIGGER_TIME;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Transition Time Limit trigger', $e->getMessage());
			$t3->trigger = TestAssets\Transition::TRIGGER_AUTO;
		}

		try
		{
			$arcs = $t3->arcs;
			unset($t3->arcs[0]);
			$v->isValid($workflow);
			$this->fail('Transition');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Transition with no input Arc', $e->getMessage());
			$t3->arcs = $arcs;
		}

		try
		{
			$arcs = $t3->arcs;
			unset($t3->arcs[1]);
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Transition with no output Arc', $e->getMessage());
			$t3->arcs = $arcs;
		}
	}

	public function testArc()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initMinimalValid();
		/* @var $a4 TestAssets\Arc */
		$a4 = $workflow->items[3];
		try
		{
			$p = $a4->place;
			$a4->place = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Place in Arc', $e->getMessage());
			$a4->place = $p;
		}

		try
		{
			$t = $a4->transition;
			$a4->transition = null;
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Transition in Arc', $e->getMessage());
			$a4->transition = $t;
		}
	}

	public function testLoop()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initLoop();
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Transition not linked', $e->getMessage());
		}
	}

	public function testOutputArcs()
	{
		$v = $this->getObject();
		$workflow = new TestAssets\Workflow();
		$workflow->initLoop();

		$a = $workflow->addArc(1, 8);
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Out Place Arc (IMPLICIT_OR_SPLIT) type: start -> SEQ -> t3', $e->getMessage());
		}

		$a->type = TestAssets\Arc::TYPE_IMPLICIT_OR_SPLIT;

		$a4 = $workflow->getArcById(4);
		$a4->type = TestAssets\Arc::TYPE_IMPLICIT_OR_SPLIT;

		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid AUTO trigger Transition on IMPLICIT_OR_SPLIT Arc. start -> IMP_OR_SPLIT -> t3', $e->getMessage());
		}
		$t = $a->getTransition();
		$t->trigger = TestAssets\Transition::TRIGGER_MSG;

		$t = $a4->getTransition();
		$t->trigger = TestAssets\Transition::TRIGGER_MSG;


		$a = $workflow->addArc(8, 2);
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Out Transition Arc (AND_SPLIT, EXPLICIT_OR_SPLIT) type: t8 -> SEQ -> p7', $e->getMessage());
		}
		$a11 = $workflow->getArcById(11);
		$a11->type = TestAssets\Arc::TYPE_EXPLICIT_OR_SPLIT;

		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Empty Pre Condition : 11/EXP_OR_SPLIT', $e->getMessage());
		}

		$a11->preCondition = 'ok';
		$a->type = TestAssets\Arc::TYPE_AND_SPLIT;
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Arc type mixing: t8 -> AND_SPLIT / EXP_OR_SPLIT  -> end', $e->getMessage());
		}

		$a->type = TestAssets\Arc::TYPE_EXPLICIT_OR_SPLIT;
		$a->preCondition = 'ko';
		try
		{
			$v->isValid($workflow);
			$this->fail('throw \RuntimeException');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('No default precondition: t8 -> EXP_OR_SPLIT  -> p7', $e->getMessage());
		}

		$a->preCondition = TestAssets\Arc::PRECONDITION_DEFAULT;
		$this->assertTrue($v->isValid($workflow));
	}
}
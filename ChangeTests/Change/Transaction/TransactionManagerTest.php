<?php
namespace ChangeTests\Change\Transaction;

use Change\Transaction\TransactionManager;

/**
 * @name \ChangeTests\Change\Transaction\TransactionManagerTest
 */
class TransactionManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testTransaction()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$this->assertFalse($tm->started());
		$this->assertEquals(0, $tm->count());
		$l = new Listener_5421181245();
		$tm->getEventManager()->attach(TransactionManager::EVENT_BEGIN, array($l, 'begin'));
		$tm->getEventManager()->attach(TransactionManager::EVENT_COMMIT, array($l, 'commit'));
		$tm->getEventManager()->attach(TransactionManager::EVENT_ROLLBACK, array($l, 'rollback'));

		$tm->begin();

		$this->assertEquals('begin', $l->type);
		$this->assertTrue($l->primary);
		$this->assertEquals(1, $l->count);
		$this->assertSame($tm, $l->event->getTarget());

		$tm->begin();

		$this->assertEquals('begin', $l->type);
		$this->assertFalse($l->primary);
		$this->assertEquals(2, $l->count);

		$tm->commit();

		$this->assertEquals('commit', $l->type);
		$this->assertFalse($l->primary);
		$this->assertEquals(2, $l->count);


		$tm->commit();

		$this->assertEquals('commit', $l->type);
		$this->assertTrue($l->primary);
		$this->assertEquals(1, $l->count);
	}

	public function testRollBack()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$this->assertFalse($tm->started());
		$this->assertEquals(0, $tm->count());
		$l = new Listener_5421181245();
		$tm->getEventManager()->attach(TransactionManager::EVENT_BEGIN, array($l, 'begin'));
		$tm->getEventManager()->attach(TransactionManager::EVENT_COMMIT, array($l, 'commit'));
		$tm->getEventManager()->attach(TransactionManager::EVENT_ROLLBACK, array($l, 'rollback'));

		$tm->begin();

		$this->assertEquals('begin', $l->type);
		$this->assertTrue($l->primary);
		$this->assertEquals(1, $l->count);
		$this->assertSame($tm, $l->event->getTarget());

		$tm->begin();

		$this->assertEquals('begin', $l->type);
		$this->assertFalse($l->primary);
		$this->assertEquals(2, $l->count);

		try
		{
			$tm->rollBack();
			$this->fail('RollbackException: Transaction cancelled');
		}
		catch (\Change\Transaction\RollbackException $e)
		{
			$this->assertEquals(120000, $e->getCode());
		}

		$this->assertEquals('rollback', $l->type);
		$this->assertFalse($l->primary);
		$this->assertEquals(2, $l->count);

		try
		{
			$tm->commit();
			$this->fail('LogicException: Transaction is dirty');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals(121002, $e->getCode());
		}

		$tm->rollBack();
		$this->assertEquals('rollback', $l->type);
		$this->assertTrue($l->primary);
		$this->assertEquals(1, $l->count);
	}
}

class Listener_5421181245 {

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var \Zend\EventManager\Event
	 */
	public $event;

	/**
	 * @var integer
	 */
	public $count;

	/**
	 * @var boolean
	 */
	public $primary;


	public function begin(\Zend\EventManager\Event $event)
	{
		$this->type = 'begin';
		$this->event = $event;
		$this->count = $event->getParam('count');
		$this->primary = $event->getParam('primary');
	}

	public function commit(\Zend\EventManager\Event $event)
	{
		$this->type = 'commit';
		$this->event = $event;
		$this->count = $event->getParam('count');
		$this->primary = $event->getParam('primary');
	}

	public function rollback(\Zend\EventManager\Event $event)
	{
		$this->type = 'rollback';
		$this->event = $event;
		$this->count = $event->getParam('count');
		$this->primary = $event->getParam('primary');
	}
}

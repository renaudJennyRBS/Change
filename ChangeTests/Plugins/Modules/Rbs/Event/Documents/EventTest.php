<?php
namespace ChangeTests\Rbs\Event\Documents;

class EventTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->getApplicationServices()->getTransactionManager()->commit();
		$this->closeDbConnection();
	}

	public function testOnOneMinute()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$timeZone = $dm->getApplicationServices()->getI18nManager()->getTimeZone();

		/* @var $event \Rbs\Event\Documents\Event */
		$event = $dm->getNewDocumentInstanceByModelName('Rbs_Event_Event');
		$event->setDate(new \DateTime('2000-01-01 12:12:00', $timeZone));
		$event->setEndDate(new \DateTime('2000-01-01 12:12:00', $timeZone));
		$this->assertTrue($event->onOneMinute());
		$event->setEndDate(new \DateTime('2000-01-01 12:12:35', $timeZone));
		$this->assertTrue($event->onOneMinute());

		$event->setEndDate(new \DateTime('2000-01-01 12:11:59', $timeZone));
		$this->assertFalse($event->onOneMinute());
		$event->setEndDate(new \DateTime('2000-01-01 12:13:00', $timeZone));
		$this->assertFalse($event->onOneMinute());

		$event->setEndDate(new \DateTime('2000-01-01 13:12:00', $timeZone));
		$this->assertFalse($event->onOneMinute());
		$event->setEndDate(new \DateTime('2000-01-03 12:12:00', $timeZone));
		$this->assertFalse($event->onOneMinute());
		$event->setEndDate(new \DateTime('2000-05-01 12:12:00', $timeZone));
		$this->assertFalse($event->onOneMinute());
		$event->setEndDate(new \DateTime('2010-01-01 12:12:00', $timeZone));
		$this->assertFalse($event->onOneMinute());
	}

	public function testOnOneDay()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$timeZone = $dm->getApplicationServices()->getI18nManager()->getTimeZone();

		/* @var $event \Rbs\Event\Documents\Event */
		$event = $dm->getNewDocumentInstanceByModelName('Rbs_Event_Event');
		$event->setDate(new \DateTime('2000-01-02 12:12:00', $timeZone));
		$event->setEndDate(new \DateTime('2000-01-02 12:12:00', $timeZone));
		$this->assertTrue($event->onOneDay());
		$event->setEndDate(new \DateTime('2000-01-02 12:13:00', $timeZone));
		$this->assertTrue($event->onOneDay());
		$event->setEndDate(new \DateTime('2000-01-02 13:12:00', $timeZone));
		$this->assertTrue($event->onOneDay());

		$event->setEndDate(new \DateTime('2000-01-01 23:59:59', $timeZone));
		$this->assertFalse($event->onOneDay());
		$event->setEndDate(new \DateTime('2000-01-02 00:00:00', $timeZone));
		$this->assertTrue($event->onOneDay());

		$event->setEndDate(new \DateTime('2000-01-02 23:59:59', $timeZone));
		$this->assertTrue($event->onOneDay());
		$event->setEndDate(new \DateTime('2000-01-03 00:00:00', $timeZone));
		$this->assertFalse($event->onOneDay());

		$event->setEndDate(new \DateTime('2000-05-02 12:12:00', $timeZone));
		$this->assertFalse($event->onOneDay());
		$event->setEndDate(new \DateTime('2010-01-02 12:12:00', $timeZone));
		$this->assertFalse($event->onOneDay());
	}
}
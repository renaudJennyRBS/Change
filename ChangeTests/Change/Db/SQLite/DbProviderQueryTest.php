<?php
namespace ChangeTests\Change\Db\SQLite;

use Change\Db\SQLite\DbProvider;
use Change\Db\ScalarType;
use Change\Db\Query\ResultsConverter;

class DbProviderQueryTest extends \ChangeTests\Change\TestAssets\TestCase
{

	protected function setUp()
	{
		if (!in_array('sqlite', \PDO::getAvailableDrivers()))
		{
			$this->markTestSkipped('PDO SQLite is not installed.');
		}

		$provider = $this->getApplicationServices()->getDbProvider();
		if (!($provider instanceof DbProvider))
		{
			$this->markTestSkipped('The SQLite DbProvider is not configured.');
		}

		$connectionInfos = $provider->getConnectionInfos();
		if (!isset($connectionInfos['database']))
		{
			$this->markTestSkipped('The SQLite database not defined!');
		}
	}

	/**
	 * @return \Change\Db\SQLite\DbProvider
	 */
	public function testGetInstance()
	{
		$provider = $this->getApplicationServices()->getDbProvider();

		$schemaManager = $provider->getSchemaManager();
		$schemaManager->clearDB();

		$testSchema = new \ChangeTests\Change\Db\SQLite\TestAssets\Schema($schemaManager);
		$testSchema->generate();

		return $provider;
	}

	/**
	 * @depends testGetInstance
	 * @param \Change\Db\SQLite\DbProvider $provider
	 * @return \Change\Db\SQLite\DbProvider
	 */
	public function testInsert(DbProvider $provider)
	{
		$sb = $provider->getNewStatementBuilder();
		$fb = $sb->getFragmentBuilder();
		$iq = $sb->insert('test_t1', 'id', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6')
			->addValues(
			$fb->integerParameter('id', $sb),
			$fb->parameter('f1', $sb),
			$fb->integerParameter('f2', $sb),
			$fb->typedParameter('f3', ScalarType::DECIMAL, $sb),
			$fb->typedParameter('f4', ScalarType::BOOLEAN, $sb),
			$fb->parameter('f5', $sb),
			$fb->dateTimeParameter('f6', $sb)
		)->insertQuery();

		$iq->bindParameter('id', 5);
		$iq->bindParameter('f1', 'CODE A1');
		$iq->bindParameter('f2', 100);
		$iq->bindParameter('f3', 56.238);
		$iq->bindParameter('f4', true);
		$iq->bindParameter('f5', 'été à bôrd');
		$iq->bindParameter('f6', new \DateTime('2013-06-05 13:50:05'));
		$iq->execute();

		$iq->bindParameter('id', 100);
		$iq->bindParameter('f1', 'CODE B1');
		$iq->bindParameter('f2', 20);
		$iq->bindParameter('f3', 10.23);
		$iq->bindParameter('f4', false);
		$iq->bindParameter('f5', 'ÉTÉ À BÔRD');
		$iq->bindParameter('f6', new \DateTime('2013-06-05 13:50:05', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq->bindParameter('id', 101);
		$iq->bindParameter('f1', 'CODE B2');
		$iq->bindParameter('f2', 50);
		$iq->bindParameter('f3', null);
		$iq->bindParameter('f4', false);
		$iq->bindParameter('f5', 'ete a bord');
		$iq->bindParameter('f6', new \DateTime('2013-06-01 13:50:05', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq->bindParameter('id', 105);
		$iq->bindParameter('f1', 'CODE B3');
		$iq->bindParameter('f2', 101);
		$iq->bindParameter('f3', 30);
		$iq->bindParameter('f4', true);
		$iq->bindParameter('f5', 'Ete à bord');
		$iq->bindParameter('f6', new \DateTime('2013-06-02 13:30:05', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq->bindParameter('id', 110);
		$iq->bindParameter('f1', 'CODE B5');
		$iq->bindParameter('f2', 101);
		$iq->bindParameter('f3', 1/3);
		$iq->bindParameter('f4', false);
		$iq->bindParameter('f5', 'Éte à BORD');
		$iq->bindParameter('f6', new \DateTime('1900-01-01 00:00:00', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq = $sb->insert('test_t2', 'id', '2f1', '2f2', '2f3', '2f4', '2f5', '2f6')
			->addValues(
			$fb->integerParameter('id', $sb),
			$fb->parameter('f1', $sb),
			$fb->integerParameter('f2', $sb),
			$fb->typedParameter('f3', ScalarType::DECIMAL, $sb),
			$fb->typedParameter('f4', ScalarType::BOOLEAN, $sb),
			$fb->parameter('f5', $sb),
			$fb->dateTimeParameter('f6', $sb)
		)->insertQuery();

		$iq->bindParameter('id', 50);
		$iq->bindParameter('f1', 'CODE A1');
		$iq->bindParameter('f2', 5);
		$iq->bindParameter('f3', 56.238);
		$iq->bindParameter('f4', true);
		$iq->bindParameter('f5', 'abc');
		$iq->bindParameter('f6', new \DateTime('2013-06-05 23:00:00', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq->bindParameter('id', 100);
		$iq->bindParameter('f1', 'CODE B1');
		$iq->bindParameter('f2', 105);
		$iq->bindParameter('f3', 10.23);
		$iq->bindParameter('f4', false);
		$iq->bindParameter('f5', 'Abc');
		$iq->bindParameter('f6', new \DateTime('2013-06-05 12:00:00', new \DateTimeZone('GMT')));
		$iq->execute();

		$iq->bindParameter('id', 101);
		$iq->bindParameter('f1', 'CODE B101');
		$iq->bindParameter('f2', 13);
		$iq->bindParameter('f3', null);
		$iq->bindParameter('f4', false);
		$iq->bindParameter('f5', 'Abcd');
		$iq->bindParameter('f6', new \DateTime('2013-06-05 01:00:00', new \DateTimeZone('GMT')));
		$iq->execute();

		return $provider;
	}

	/**
	 * @depends testInsert
	 * @param \Change\Db\SQLite\DbProvider $provider
	 * @return \Change\Db\SQLite\DbProvider
	 */
	public function testSelect(DbProvider $provider)
	{
		$qb = $provider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select()->from($fb->table('test_t1'))->orderDesc($fb->column('id'));

		$sq = $qb->query();
		$sq->setMaxResults(1);
		$sq->setStartIndex(1);

		$converter = new ResultsConverter($provider, array('id' => ScalarType::INTEGER, 'f3' => ScalarType::DECIMAL, 'f4' => ScalarType::BOOLEAN, 'f6' => ScalarType::DATETIME));

		$rows = $sq->getResults(function($rows) use ($converter) {return $converter->convertRows($rows);});
		$this->assertCount(1, $rows);
		$this->assertEquals(105, $rows[0]['id']);

		$qb->select($fb->alias($fb->column('id', 't1'), 'id'), $fb->alias($fb->column('2f6', 't2'), 'f6'))
			->from($fb->alias($fb->table('test_t1'), 't1'))
			->innerJoin($fb->alias($fb->table('test_t2'), 't2'), $fb->column('id'))
			->orderAsc($fb->identifier('f6'));
		$sq = $qb->query();

		$rows = $sq->getResults(function($rows) use ($converter) {return $converter->convertRows($rows);});
		$this->assertCount(2, $rows);
		$this->assertEquals(101, $rows[0]['id']);
		$this->assertEquals(100, $rows[1]['id']);

		return $provider;
	}
}

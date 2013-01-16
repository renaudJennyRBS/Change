<?php
namespace ChangeTests\Change\Db\Query;

class FakeAbstractQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '';
	}
}


class AbstractQueryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
	
	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	protected function getSQLFragmentBuilder()
	{
		return new \Change\Db\Query\SQLFragmentBuilder($this->getDbProvider()->getSqlMapping());
	}
	
	public function testConstruct()
	{
		$abstractQuery = new FakeAbstractQuery($this->getDbProvider());
		$this->assertTrue(is_array($abstractQuery->getParameters()));
		
		$this->assertNull($abstractQuery->getOptions());
		return $abstractQuery;
	}
	
	/**
	 * @depends testConstruct
	 * @param FakeAbstractQuery $abstractQuery
	 */
	public function testParameters($abstractQuery)
	{
		$p1 = $this->getSQLFragmentBuilder()->parameter('p1');
		$p2 = $this->getSQLFragmentBuilder()->parameter('p2');
		$ret = $abstractQuery->addParameter($p1);
		$this->assertEquals($ret, $abstractQuery);
		$this->assertCount(1, $abstractQuery->getParameters());
		
		$abstractQuery->setParameters(array($p1, $p2));
		$this->assertCount(2, $abstractQuery->getParameters());
		
		try
		{
			$abstractQuery->addParameter($p2);
			$this->fail('Parameter p2 already exist');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}
		
		$p3 = $this->getSQLFragmentBuilder()->parameter('p3');
		try
		{
			$abstractQuery->setParameters(array($p3, $p3));
			$this->fail('Parameter p3 already exist');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}

		$abstractQuery->setParameters(array());
		$this->assertCount(0, $abstractQuery->getParameters());
	}
}
<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\Query;
use Change\Documents\Query\ChildBuilder;
use ChangeTests\Change\TestAssets\TestCase;

class ChildBuilderTest extends TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}


	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testInitializeDB()
	{
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');


		$childBuilder = new ChildBuilder($builder, 'Project_Tests_Localized', 'id', 'id');
		$this->assertSame($builder, $childBuilder->getParent());
		$this->assertSame($builder, $childBuilder->getQuery());
		$this->assertSame($this->getApplicationServices()->getDbProvider(), $builder->getDbProvider());
		$this->assertEquals('Project_Tests_Localized', $childBuilder->getModel()->getName());

		try
		{
			new ChildBuilder($builder, null, 'id', 'id');
			$this->fail('Argument 2 must be a valid model');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 2 must be a valid', $e->getMessage());
		}

		try
		{
			new ChildBuilder($builder, 'Project_Tests_Localized', null, 'id');
			$this->fail('Argument 3 must be a valid property');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 3 must be a valid', $e->getMessage());
		}

		try
		{
			new ChildBuilder($builder, 'Project_Tests_Localized', 'id', null);
			$this->fail('Argument 4 must be a valid property');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 4 must be a valid', $e->getMessage());
		}
	}
}
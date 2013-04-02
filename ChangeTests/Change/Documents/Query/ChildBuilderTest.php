<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\Builder;
use Change\Documents\Query\ChildBuilder;
use ChangeTests\Change\TestAssets\TestCase;

class ChildBuilderTest extends TestCase
{

	static public function setUpBeforeClass()
	{
		$app = static::getNewApplication();

		$appServices = static::getNewApplicationServices($app);

		$compiler = new \Change\Documents\Generators\Compiler($app, $appServices);
		$compiler->generate();

		$appServices->getDbProvider()->getSchemaManager()->clearDB();
		$generator = new \Change\Db\Schema\Generator($app->getWorkspace(), $appServices->getDbProvider());
		$generator->generate();

	}

	public static function tearDownAfterClass()
	{
		$dbp =  static::getNewApplicationServices(static::getNewApplication())->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
	}

	public function testInitializeDB()
	{
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{

		$builder = new Builder($this->getDocumentServices(), 'Project_Tests_Basic');


		$childBuilder = new ChildBuilder($builder, 'Project_Tests_Localized', 'id', 'id');
		$this->assertSame($builder, $childBuilder->getParent());
		$this->assertSame($builder, $childBuilder->getMaster());
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
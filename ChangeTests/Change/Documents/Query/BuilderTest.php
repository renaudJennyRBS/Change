<?php
namespace ChangeTests\Change\Documents\Query;

use \Change\Documents\Query\Builder;

class BuilderTest extends \ChangeTests\Change\TestAssets\TestCase
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
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$dm = $this->getDocumentServices()->getDocumentManager();
		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1000);
		$basicDoc->setPStr('Test 1000');
		$basicDoc->setPInt(1001);
		$basicDoc->setPDocId(1002);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1001);
		$basicDoc->setPStr('1001 Test');
		$basicDoc->setPInt(1001);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1002);
		$basicDoc->setPStr('1002');
		$basicDoc->setPInt(7);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$dm->pushLCID('fr_FR');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1005);
		$localizedDoc->setPStr('test 1005');
		$localizedDoc->setPLStr('text un');
		$localizedDoc->setPInt(1001);
		$localizedDoc->setPLInt(1001);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1000));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1000), $dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_GB');
		$localizedDoc->setPLStr('text one');
		$localizedDoc->setPLInt(1002);
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_GB');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1006);
		$localizedDoc->setPStr('test 1006');
		$localizedDoc->setPLStr('text two');
		$localizedDoc->setPLInt(7);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1001));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_GB');
		$localizedDoc->setPLStr('text one');
		$localizedDoc->save();
		$dm->popLCID();
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = new Builder($this->getDocumentServices(), $model);
		$this->assertSame($this->getDocumentServices(), $builder->getDocumentServices());
		$this->assertSame($this->getApplicationServices()->getDbProvider(), $builder->getDbProvider());
		$this->assertSame($model, $builder->getModel());
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetFirstDocument()
	{
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = new Builder($this->getDocumentServices(), $model);
		$doc = $builder->getFirstDocument();
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $doc);
	}


	/**
	 * @depends testGetFirstDocument
	 */
	public function testGetDocuments()
	{
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = new Builder($this->getDocumentServices(), $model);
		$documents = $builder->getDocuments();
		$this->assertInstanceOf('\Change\Documents\DocumentCollection', $documents);
		$this->assertEquals(3, $documents->count());
	}


	/**
	 * @depends testGetDocuments
	 */
	public function testGetCountDocuments()
	{
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = new Builder($this->getDocumentServices(), $model);
		$this->assertEquals(3, $builder->getCountDocuments());
	}
}
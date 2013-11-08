<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\TreePredicateBuilder;
use ChangeTests\Change\TestAssets\TestCase;

class TreePredicateBuilderTest extends TestCase
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
		$this->getApplicationServices()->getTransactionManager()->commit();
		parent::tearDown();
	}

	public function testInitializeDB()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$dm = $this->getApplicationServices()->getDocumentManager();
		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1000);
		$basicDoc->setPStr('Test 1000');
		$basicDoc->setPInt(1001);
		$basicDoc->setPFloat(5.0);
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
		$localizedDoc->getCurrentLocalization()->setPLStr('text un');
		$localizedDoc->setPInt(1001);
		$localizedDoc->getCurrentLocalization()->setPLInt(1001);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1000));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1000), $dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->getCurrentLocalization()->setPLInt(1002);
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1006);
		$localizedDoc->setPStr('test 1006');
		$localizedDoc->getCurrentLocalization()->setPLStr('text two');
		$localizedDoc->getCurrentLocalization()->setPLInt(7);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1001));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->save();
		$dm->popLCID();

		$treeManager = $this->getApplicationServices()->getTreeManager();
		/* @var $folderDoc \Rbs\Generic\Documents\Folder */
		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('Root');
		$folderDoc->initialize(2000);
		$folderDoc->save();
		$rn = $treeManager->insertRootNode($folderDoc, 'Project_Tests');

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 1');
		$folderDoc->initialize(2001);
		$folderDoc->save();
		$treeManager->insertNode($rn, $folderDoc);
		$treeManager->insertNode($rn, $dm->getDocumentInstance(1000));

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 10');
		$folderDoc->initialize(2010);
		$folderDoc->save();
		$n = $treeManager->insertNode($treeManager->getNodeById(2001), $folderDoc);
		$treeManager->insertNode($n, $dm->getDocumentInstance(1001));
		$treeManager->insertNode($n, $dm->getDocumentInstance(1002));

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 11');
		$folderDoc->initialize(2011);
		$folderDoc->save();
		$treeManager->insertNode($n, $folderDoc, $treeManager->getNodeById(1002));

		/* @var $corDoc \Project\Tests\Documents\Correction */
		$corDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$corDoc->initialize(3000);
		$corDoc->setLabel('C0');
		$corDoc->save();

		$corDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$corDoc->initialize(3001);
		$corDoc->setLabel('C1');
		$corDoc->save();
		$corDoc->getCurrentLocalization()->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
		$corDoc->save();
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$this->assertNotNull($pb);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testChildOf()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->childOf(2000));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1000, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->childOf(2001));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->childOf(2010));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1002, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testDescendantOf()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->descendantOf(2000));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(3, $ids);
		$this->assertContains(1000, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1002, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->descendantOf(1000));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testAncestorOf()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->ancestorOf(2000));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Generic_Folder');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->ancestorOf(2011));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(3, $ids);
		$this->assertContains(2000, $ids);
		$this->assertContains(2001, $ids);
		$this->assertContains(2010, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testNextSiblingOf()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->nextSiblingOf(2011));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1002, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testPreviousSiblingOf()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new TreePredicateBuilder($builder, $this->getApplicationServices()->getTreeManager());
		$builder->andPredicates($pb->previousSiblingOf(1002));
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1001, $ids);
	}
}

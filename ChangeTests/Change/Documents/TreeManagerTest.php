<?php
namespace ChangeTests\Change\Documents;


/**
 * @name \ChangeTests\Change\Documents\TreeManagerTest
 */
class TreeManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function tearDownAfterClass()
	{
		$dbp =  static::getNewApplicationServices(static::getNewApplication())->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
	}

	/**
	 * @return \Change\Documents\TreeManager
	 */
	public function testInitializeDB()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$compiler->generate();

		$generator = new \Change\Db\Schema\Generator($this->getApplication()->getWorkspace(), $this->getApplicationServices()->getDbProvider());
		$generator->generate();

		$treeManager = $this->getDocumentServices()->getTreeManager();

		return $treeManager;
	}

	/**
	 * @depends testInitializeDB
	 * @param \Change\Documents\TreeManager $treeManager
	 * @return \Change\Documents\TreeManager
	 */
	public function testCreate(\Change\Documents\TreeManager $treeManager)
	{
		$treeManager->createTree('Project_Tests');

		/* @var $testsBasicService \Project\Tests\Documents\BasicService */
		$testsBasicService = $this->getDocumentServices()->getProjectTestsBasic();

		$doc = $testsBasicService->getNewDocumentInstance();
		$doc->setPStr('root Node');
		$doc->save();
		$this->assertNull($doc->getTreeName());
		$rootId = $doc->getId();

		$tn = $treeManager->insertRootNode($doc, 'Project_Tests');
		$this->assertEquals('Project_Tests', $doc->getTreeName());
		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn);

		$this->assertEquals('Project_Tests', $tn->getTreeName());

		$this->assertEquals('/', $tn->getPath());

		$this->assertTrue($tn->isRoot());
		$this->assertEquals(0, $tn->getParentId());
		$this->assertEquals(0, $tn->getPosition());
		$this->assertEquals(0, $tn->getChildrenCount());
		$this->assertEquals($rootId, $tn->getDocumentId());

		$doc1 = $testsBasicService->getNewDocumentInstance();
		$doc1->setPStr('Node 1');
		$doc1->save();

		$tn1 = $treeManager->insertNode($tn, $doc1);
		$this->assertEquals('Project_Tests', $doc1->getTreeName());

		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn1);
		$this->assertEquals('Project_Tests', $tn1->getTreeName());
		$this->assertEquals('/' . $rootId . '/', $tn1->getPath());
		$this->assertFalse($tn1->isRoot());
		$this->assertEquals($rootId, $tn1->getParentId());
		$this->assertEquals(0, $tn1->getPosition());
		$this->assertEquals(0, $tn1->getChildrenCount());
		$this->assertEquals($doc1->getId(), $tn1->getDocumentId());


		$doc2 = $testsBasicService->getNewDocumentInstance();
		$doc2->setPStr('Node 2');
		$doc2->save();

		$tn2 = $treeManager->insertNode($tn, $doc2, $tn1);
		$this->assertEquals('Project_Tests', $doc2->getTreeName());

		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn2);
		$this->assertEquals('Project_Tests', $tn2->getTreeName());
		$this->assertEquals('/' . $rootId . '/', $tn2->getPath());
		$this->assertFalse($tn2->isRoot());
		$this->assertEquals($rootId, $tn2->getParentId());
		$this->assertEquals(0, $tn2->getPosition());
		$this->assertEquals(0, $tn2->getChildrenCount());
		$this->assertEquals($doc2->getId(), $tn2->getDocumentId());

		return $treeManager;
	}

	/**
	 * @depends testCreate
	 * @param \Change\Documents\TreeManager $treeManager
	 * @return \Change\Documents\TreeNode
	 */
	public function testGetNode(\Change\Documents\TreeManager $treeManager)
	{
		$treeManager->reset();
		$rtn = $treeManager->getRootNode('Project_Tests');
		$this->assertInstanceOf('\Change\Documents\TreeNode', $rtn);

		$this->assertTrue($rtn->hasChildren());
		$this->assertEquals(2, $rtn->getChildrenCount());
		$children = $rtn->getChildren();
		$this->assertCount(2, $children);
		$this->assertInstanceOf('\Change\Documents\TreeNode', $children[0]);
		$this->assertInstanceOf('\Change\Documents\TreeNode', $children[1]);

		$this->assertEquals(1, $children[1]->getPosition());
		return $rtn;
	}

	/**
	 * @depends testGetNode
	 * @param \Change\Documents\TreeNode $rootNode
	 */
	public function testDeleteNode(\Change\Documents\TreeNode $rootNode)
	{
		$treeManager = $rootNode->getTreeManager();
		$dm = $treeManager->getDocumentServices()->getDocumentManager();

		$this->assertInstanceOf('\Change\Documents\TreeManager', $treeManager);

		$children = $rootNode->getChildren();
		$doc2 = $children[0]->getDocument();
		$this->assertEquals('Project_Tests', $doc2->getTreeName());
		$doc1 = $children[1]->getDocument();
		$this->assertEquals('Project_Tests', $doc1->getTreeName());

		$treeManager->deleteDocumentNode($doc2);

		$this->assertNull($doc2->getTreeName());

		$this->assertEquals(1, $rootNode->getChildrenCount());

		$treeManager->deleteChildrenNodes($rootNode);
		$this->assertNull($doc1->getTreeName());

		$dm->reset();

		$doc1Bis = $dm->getDocumentInstance($doc1->getId());
		$this->assertNull($doc1Bis->getTreeName());

		$rootDoc = $rootNode->getDocument();
		$treeManager->deleteDocumentNode($rootDoc);
		$this->assertNull($rootDoc->getTreeName());


	}
}

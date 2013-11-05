<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\TreeNode;

/**
 * @name \ChangeTests\Change\Documents\TreeManagerTest
 */
class TreeManagerTest extends \ChangeTests\Change\TestAssets\TestCase
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

	/**
	 * @param string $label
	 * @return \Project\Tests\Documents\Basic
	 */
	protected function getNewBasicDoc($label = 'node')
	{
		/* @var $doc \Project\Tests\Documents\Basic */
		$doc = $this->getApplicationServices()->getDocumentManager()
			->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$doc->setPStr($label);
		$doc->save();
		return $doc;
	}

	/**
	 * @param integer $id
	 * @param string $label
	 * @return bool
	 */
	protected function checkBasicDocLabel($id, $label)
	{
		/* @var $doc \Project\Tests\Documents\Basic */
		$doc = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($id);
		if ($doc instanceof \Project\Tests\Documents\Basic)
		{
			return $doc->getPStr() === $label;
		}
		return false;
	}

	/**
	 * @return \Change\Documents\TreeManager
	 */
	protected function getTreeManager()
	{
		return $this->getApplicationServices()->getTreeManager();
	}


	public function testInitializeDB()
	{
		$treeManager = $this->getTreeManager();
		$this->assertInstanceOf('\Change\Documents\TreeManager', $treeManager);
	}

	public function testTreeNames()
	{
		$treeManager = $this->getTreeManager();
		$treeNames = $treeManager->getTreeNames();
		$this->assertContains('Project_Tests', $treeNames);
		$this->assertTrue($treeManager->hasTreeName('Project_Tests'));
		$this->assertFalse($treeManager->hasTreeName('Project_NotFound'));
	}

	public function testCreate()
	{
		$treeManager = $this->getTreeManager();

		/* @var $doc \Project\Tests\Documents\Basic */
		$doc = $this->getNewBasicDoc('Root Node');
		$this->assertNull($doc->getTreeName());
		$rootId = $doc->getId();

		$rootNode = $treeManager->insertRootNode($doc, 'Project_Tests');
		$this->assertEquals('Project_Tests', $doc->getTreeName());

		$this->assertInstanceOf('\Change\Documents\TreeNode', $rootNode);
		$this->assertEquals('Project_Tests', $rootNode->getTreeName());
		$this->assertEquals('/', $rootNode->getPath());
		$this->assertTrue($rootNode->isRoot());
		$this->assertEquals(0, $rootNode->getParentId());
		$this->assertEquals(0, $rootNode->getPosition());
		$this->assertEquals(0, $rootNode->getChildrenCount());
		$this->assertEquals($rootId, $rootNode->getDocumentId());

		$tmpNode = $treeManager->insertRootNode($doc, 'Project_Tests');
		$this->assertTrue($rootNode->eq($tmpNode));

		$doc1 = $this->getNewBasicDoc('Node 1');
		try
		{
			$treeManager->insertRootNode($doc1, 'Project_Tests');
			$this->fail('RuntimeException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Root node ('. $rootNode.' ) already exist.', $e->getMessage());
		}

		$tn1 = $treeManager->insertNode($rootNode, $doc1);
		$this->assertEquals('Project_Tests', $doc1->getTreeName());

		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn1);
		$this->assertEquals('Project_Tests', $tn1->getTreeName());
		$this->assertEquals($rootNode->getFullPath(), $tn1->getPath());

		$this->assertFalse($tn1->isRoot());
		$this->assertEquals($rootId, $tn1->getParentId());
		$this->assertEquals(0, $tn1->getPosition());
		$this->assertEquals(0, $tn1->getChildrenCount());
		$this->assertEquals($doc1->getId(), $tn1->getDocumentId());


		$doc2 = $this->getNewBasicDoc('Node 2');

		$tn2 = $treeManager->insertNode($rootNode, $doc2, $tn1);
		$this->assertEquals('Project_Tests', $doc2->getTreeName());

		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn2);
		$this->assertEquals('Project_Tests', $tn2->getTreeName());
		$this->assertEquals($rootNode->getFullPath(), $tn2->getPath());
		$this->assertEquals($rootId, $tn2->getParentId());
		$this->assertEquals(0, $tn2->getPosition());
		$this->assertEquals(0, $tn2->getChildrenCount());
		$this->assertEquals($doc2->getId(), $tn2->getDocumentId());
	}

	/**
	 * @depends testCreate
	 */
	public function testGetNode()
	{
		$treeManager = $this->getTreeManager();

		$rootNode = $treeManager->getRootNode('Project_Tests');

		$this->assertInstanceOf('\Change\Documents\TreeNode', $rootNode);

		$this->assertTrue($rootNode->hasChildren());
		$this->assertEquals(2, $rootNode->getChildrenCount());

		$children = $treeManager->getChildrenNode($rootNode);

		$this->assertCount(2, $children);

		$tn1 = $children[0];
		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn1);
		$this->assertEquals(0, $tn1->getPosition());

		$this->assertTrue($this->checkBasicDocLabel($tn1->getDocumentId(), 'Node 2'));

		$tn2 = $children[1];
		$this->assertInstanceOf('\Change\Documents\TreeNode', $tn2);
		$this->assertEquals(1, $tn2->getPosition());

		$this->assertTrue($this->checkBasicDocLabel($tn2->getDocumentId(), 'Node 1'));
	}

	/**
	 * @depends testGetNode
	 */
	public function testDeleteNode()
	{
		$treeManager = $this->getTreeManager();
		$rootNode = $treeManager->getRootNode('Project_Tests');
		$this->assertEquals(2, $rootNode->getChildrenCount());

		$children = $treeManager->getChildrenNode($rootNode);

		$doc2 = $children[0]->setTreeManager($treeManager)->getDocument();
		$doc2->load();

		$doc1 = $children[1]->setTreeManager($treeManager)->getDocument();

		$treeManager->deleteDocumentNode($doc2);
		$this->assertNull($doc2->getTreeName());

		$this->assertEquals(1, $treeManager->getRootNode('Project_Tests')->getChildrenCount());

		$treeManager->deleteChildrenNodes($rootNode);
		$this->assertNull($doc1->getTreeName());


		$rootDoc = $rootNode->setTreeManager($treeManager)->getDocument();
		$treeManager->deleteNode($rootNode);

		$this->assertNull($rootDoc->getTreeName());

		$this->assertNull($treeManager->getRootNode('Project_Tests'));
	}


	public function testDescendant()
	{
		$treeManager = $this->getTreeManager();
		$rd = $this->getNewBasicDoc('root');
		$rootNode = $treeManager->insertRootNode($rd, 'Project_Tests');

		$n1 = $treeManager->insertNode($rootNode, $this->getNewBasicDoc('lvl 1,0'));
		$n2 = $treeManager->insertNode($rootNode, $this->getNewBasicDoc('lvl 1,1'));

		$n11 = $treeManager->insertNode($n1, $this->getNewBasicDoc('lvl 2,0'));
		$n12 = $treeManager->insertNode($n1, $this->getNewBasicDoc('lvl 2,1'));

		$n21 = $treeManager->insertNode($n2, $this->getNewBasicDoc('lvl 2,3'));
		$n22 = $treeManager->insertNode($n2, $this->getNewBasicDoc('lvl 2,4'));
		$n23 = $treeManager->insertNode($n2, $this->getNewBasicDoc('lvl 2,5'));

		$n31 = $treeManager->insertNode($n22, $this->getNewBasicDoc('lvl 3,0'));


		$nodes = $treeManager->getDescendantNodes($rootNode);
		$this->assertCount(2, $nodes);
		$this->assertTrue($n1->eq($nodes[0]));
		$this->assertTrue($n2->eq($nodes[1]));

		$n1Nodes = $nodes[0]->getChildren();
		$this->assertEquals(2, $nodes[0]->getChildrenCount());
		$this->assertCount(2, $n1Nodes);
		$this->assertTrue($n11->eq($n1Nodes[0]));
		$this->assertTrue($n12->eq($n1Nodes[1]));

		$n2Nodes = $nodes[1]->getChildren();
		$this->assertEquals(3, $nodes[1]->getChildrenCount());
		$this->assertCount(3, $n2Nodes);
		$this->assertTrue($n21->eq($n2Nodes[0]));
		$this->assertTrue($n22->eq($n2Nodes[1]));
		$this->assertTrue($n23->eq($n2Nodes[2]));

		$this->assertCount(0, $n2Nodes[0]->getChildren());
		$this->assertCount(0, $n2Nodes[2]->getChildren());

		$n22Nodes = $n2Nodes[1]->getChildren();
		$this->assertCount(1, $n22Nodes);
		$this->assertTrue($n31->eq($n22Nodes[0]));

		$this->assertCount(0, $n22Nodes[0]->getChildren());

		$nodes = $treeManager->getDescendantNodes($rootNode, 1);
		$this->assertCount(2, $nodes);
		$this->assertCount(0, $nodes[0]->getChildren());
		$this->assertCount(0, $nodes[1]->getChildren());



		$treeManager->moveNode($n1, $n31);
		$this->assertTrue($treeManager->refreshNode($n1));
		$this->assertEquals(2, $n1->getChildrenCount());
		$this->assertEquals(4, $n1->getLevel());
		$this->assertEquals($n31->getDocumentId(), $n1->getParentId());
		$this->assertEquals($n31->getFullPath(), $n1->getPath());


		$treeManager->moveNode($n1, $n2, $n22);
		$this->assertTrue($treeManager->refreshNode($n1));
		$this->assertEquals(2, $n1->getLevel());
		$this->assertEquals($n2->getFullPath(), $n1->getPath());
		$this->assertEquals(1, $n1->getPosition());
		$this->assertTrue($treeManager->refreshNode($n22));
		$this->assertEquals(2, $n22->getPosition());


		$treeManager->moveNode($n1, $n2);
		$treeManager->refreshNode($n1);
		$this->assertEquals(3, $n1->getPosition());


		$treeManager->moveNode($n1, $n2, $n21);
		$treeManager->refreshNode($n1);
		$this->assertEquals(0, $n1->getPosition());

		$treeManager->moveNode($n1, $n2, $n23);
		$treeManager->refreshNode($n1);
		$this->assertEquals(2, $n1->getPosition());
	}

	protected function dumpNode(TreeNode $node, $indent = 0)
	{
		if ($indent === 0)
		{
			echo PHP_EOL, PHP_EOL;
		}
		echo str_repeat("\t", $indent), $node, PHP_EOL;
		foreach ($node->getChildren() as $subNode)
		{
			$this->dumpNode($subNode, $indent + 1);
		}
	}

}

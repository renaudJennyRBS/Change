<?php
namespace ChangeTests\Change\Documents;

/**
 * @name \ChangeTests\Change\Documents\TreeNodeTest
 */
class TreeNodeTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testConstructor()
	{
		$node = new \Change\Documents\TreeNode('test');
		$this->assertEquals('test', $node->getTreeName());
		$this->assertNull($node->getParentId());
		$this->assertNull($node->getDocumentId());
		$this->assertNull($node->getLevel());
		$this->assertNull($node->getPath());
		$this->assertNull($node->getPosition());
		$this->assertNull($node->getChildrenCount());

		$node = new \Change\Documents\TreeNode('test', 1);
		$this->assertEquals(1, $node->getDocumentId());
	}

	public function testProperties()
	{
		$node = new \Change\Documents\TreeNode('test');
		$this->assertSame($node, $node->setTreeName('Project_Tests'));
		$this->assertEquals('Project_Tests', $node->getTreeName());

		$this->assertSame($node, $node->setDocumentId(1));
		$this->assertEquals(1, $node->getDocumentId());

		$this->assertSame($node, $node->setLevel(2));
		$this->assertEquals(2, $node->getLevel());

		$this->assertSame($node, $node->setParentId(3));
		$this->assertEquals(3, $node->getParentId());

		$this->assertSame($node, $node->setPosition(4));
		$this->assertEquals(4, $node->getPosition());

		$this->assertSame($node, $node->setPath('/'));
		$this->assertEquals('/', $node->getPath());

		$this->assertSame($node, $node->setChildrenCount(5));
		$this->assertEquals(5, $node->getChildrenCount());
	}

	public function testTreeManager()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests');
		$this->assertSame($node, $node->setTreeManager(null));
		try
		{
			$node->getTreeManager();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('TreeManager not set.', $e->getMessage());
		}
		$tm = $this->getApplicationServices()->getTreeManager();
		$node->setTreeManager($tm);
		$this->assertSame($tm, $node->getTreeManager());
	}



	public function testIsRoot()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests', 10);
		$this->assertTrue($node->isRoot());
		$this->assertTrue($node->setParentId(0)->isRoot());
		$this->assertFalse($node->setParentId(5)->isRoot());
	}

	public function testHasChildren()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests', 10);
		$this->assertFalse($node->hasChildren());
		$this->assertTrue($node->setChildrenCount(5)->hasChildren());
		$this->assertFalse($node->setChildrenCount(0)->hasChildren());
	}

	public function testEq()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests', 10);
		$node2 = new \Change\Documents\TreeNode('Project_Tests', 10);
		$this->assertTrue($node->eq($node));
		$this->assertTrue($node->eq($node2));
		$this->assertTrue($node->eq(10));

		$node3 = new \Change\Documents\TreeNode('Project_Tests', 11);
		$this->assertFalse($node->eq($node3));
		$this->assertFalse($node->eq(11));
		$this->assertFalse($node->eq('a'));
	}

	public function testPath()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests', 10);
		$node->setPath('/');
		$this->assertEquals('/10/', $node->getFullPath());
		$this->assertEquals(array(), $node->getAncestorIds());

		$node->setPath('/1/2/3/');
		$this->assertCount(3, $node->getAncestorIds());
		$this->assertEquals(array(1,2,3), $node->getAncestorIds());

		$node2 = new \Change\Documents\TreeNode('Project_Tests', 1);
		$this->assertTrue($node2->ancestorOf($node));
		$this->assertFalse($node2->setDocumentId(10)->ancestorOf($node));
	}

	public function testChildren()
	{
		$n2 =  new \Change\Documents\TreeNode('Project_Tests', 2);
		$node = new \Change\Documents\TreeNode('Project_Tests', 10);
		$this->assertSame($node, $node->setChildren(array($n2)));
		$this->assertEquals(array($n2), $node->getChildren());
		$node->setChildren(null);
		try
		{
			$node->getChildren();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('TreeManager not set.', $e->getMessage());
		}
	}

	public function testDocumentProperty()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests');
		$node->setTreeManager($this->getApplicationServices()->getTreeManager());

		$mi = new \ChangeTests\Change\Documents\TestAssets\MemoryInstance();
		$doc = $mi->getInstanceRo5001($this->getApplicationServices()->getDocumentManager());
		$node->setDocumentId(5001);
		$doc2 = $node->getDocument();
		$this->assertSame($doc, $doc2);

		$node->setTreeManager(null);
		try
		{
			$node->getDocument();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('TreeManager not set.', $e->getMessage());
		}
	}
}

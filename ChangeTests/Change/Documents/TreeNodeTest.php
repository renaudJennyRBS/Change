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
	}

	public function testProperties()
	{
		$node = new \Change\Documents\TreeNode('test');
		$node->setTreeName('Project_Tests');
		$this->assertEquals('Project_Tests', $node->getTreeName());

		$node->setDocumentId(1);
		$this->assertEquals(1, $node->getDocumentId());

		$node->setLevel(2);
		$this->assertEquals(2, $node->getLevel());

		$node->setParentId(3);
		$this->assertEquals(3, $node->getParentId());

		$node->setPosition(4);
		$this->assertEquals(4, $node->getPosition());

		$node->setPath('/');
		$this->assertEquals('/', $node->getPath());

		$node->setChildrenCount(5);
		$this->assertEquals(5, $node->getChildrenCount());
	}

	public function testDocumentProperty()
	{
		$node = new \Change\Documents\TreeNode('Project_Tests');
		$node->setTreeManager($this->getDocumentServices()->getTreeManager());

		$mi = new \ChangeTests\Change\Documents\TestAssets\MemoryInstance();
		$doc = $mi->getInstanceRo5001($this->getDocumentServices());
		$node->setDocumentId(5001);
		$doc2 = $node->getDocument();
		$this->assertSame($doc, $doc2);
	}
}

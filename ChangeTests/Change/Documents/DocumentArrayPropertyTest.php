<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\DocumentArrayProperty;

/**
* @name \ChangeTests\Change\Documents\DocumentArrayPropertyTest
*/
class DocumentArrayPropertyTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return DocumentArrayProperty
	 */
	protected function getObject()
	{
		return new DocumentArrayProperty($this->getApplicationServices()->getDocumentManager(), null);
	}

	/**
	 * @param integer $id
	 * @return \Project\Tests\Documents\Basic
	 */
	protected function getTestDoc($id)
	{
		return $this->getNewReadonlyDocument('Project_Tests_Basic', $id);
	}

	public function testConstruct()
	{
		$o = $this->getObject();
		$this->assertNull($o->getModelName());
		$this->assertCount(0, $o);
		$this->assertSame(0, $o->count());
		$this->assertFalse(isset($o[5]));
		$this->assertSame(array(), $o->getIds());
		$this->assertNull($o->getDefaultIds());
		$this->assertNull($o->getDefaultDocuments());
		$this->assertSame(array(), $o->toArray());
	}

	public function testSetModelName()
	{
		$o = $this->getObject();
		$o->setModelName('Project_Tests_Basic');
		$this->assertEquals('Project_Tests_Basic', $o->getModelName());
	}

	public function testSetDocument()
	{
		$o = $this->getObject();
		$o->setModelName('Project_Tests_Basic');
		$doc1 = $this->getTestDoc(200);
		$o->add($doc1);
		$this->assertCount(1, $o);
		$this->assertSame(1, $o->count());
		$this->assertSame($doc1, $o[0]);

		$o->add($doc1);
		$this->assertCount(1, $o);

		$o = $this->getObject();
		$o[] = $doc1;
		$this->assertCount(1, $o);
		$o[] = $doc1;
		$this->assertCount(1, $o);

		$o = $this->getObject();
		$o->fromArray(array($doc1, $doc1));
		$this->assertCount(1, $o);
		$this->assertSame(1, $o->count());

		$o = $this->getObject();
		$o->fromArray(array($doc1, $doc1, 8));
		$this->assertCount(1, $o);

		$o = $this->getObject();
		$o->fromIds(array(200, '200', 'a'));
		$this->assertCount(1, $o);
		$this->assertSame($doc1, $o[0]);
	}

	public function testDefault()
	{
		$o = $this->getObject();
		$o->setModelName('Project_Tests_Basic');
		$doc1 = $this->getTestDoc(200);
		$doc2 = $this->getTestDoc(201);
		$this->assertNull($o->getDefaultIds());
		$o->add($doc1);
		$this->assertSame(array(), $o->getDefaultIds());

		$o = $this->getObject();
		$o->setModelName('Project_Tests_Basic');

		$o->setDefaultIds(array(201));
		$this->assertNull($o->getDefaultIds());

		$this->assertSame(array(201), $o->getIds());

		$o->add($doc2);
		$this->assertSame(array(201), $o->getIds());

		$this->assertNull($o->getDefaultIds());

		$o->add($doc1);
		$this->assertSame(array(201, 200), $o->getIds());
		$this->assertSame(array(201), $o->getDefaultIds());

		unset($o[1]);
		$this->assertSame(array(201), $o->getIds());
		$this->assertNull($o->getDefaultIds());

		$o = $this->getObject();
		$o->setDefaultIds(array(200, 201));
		$o->fromIds(array());
		$this->assertSame(array(200, 201), $o->getDefaultIds());
		$this->assertCount(0, $o);

		$o->fromIds(array(201, 200));
		$this->assertSame(array(200, 201), $o->getDefaultIds());
		$this->assertCount(2, $o);

		$o->fromIds(array(200, 201));
		$this->assertNull($o->getDefaultIds());
		$this->assertCount(2, $o);

	}

	public function testUnset()
	{
		$o = $this->getObject();
		$o->setModelName('Project_Tests_Basic');
		$doc1 = $this->getTestDoc(200);
		$doc2 = $this->getTestDoc(201);
		$o->add($doc1)->add($doc2);
		$this->assertCount(2, $o);
		unset($o[0]);
		$this->assertCount(1, $o);
		$this->assertSame(array(201), $o->getIds());
		$this->assertTrue(isset($o[0]));
		$o->add($doc1);
		$this->assertSame(array(201, 200), $o->getIds());
		$o->fromIds(array());
		$this->assertCount(0, $o);
	}
}
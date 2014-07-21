<?php
namespace ChangeTests\Change\Documents;


class AbstractInlineTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		//static::clearDB();
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


	public function testInline()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();

		/* @var $document \Project\Tests\Documents\Inline */
		$document = $documentManager->getNewDocumentInstanceByModelName('Project_Tests_Inline');
		$this->assertInstanceOf('\Project\Tests\Documents\Inline', $document);
		$this->assertEquals('Project_Tests_Inline', $document->getDocumentModelName());

		$this->assertNull($document->getPInline());

		/** @var $id \Project\Tests\Documents\InlinePInline */
		$id = $document->newInlinePInline();
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInline', $id);
		$id->setTest('Test string');

		$document->setPInline($id);
		$this->assertTrue($document->isPropertyModified('pInline'));

		$document->setPInline(null);
		$this->assertFalse($document->isPropertyModified('pInline'));

		$document->setPInline($id);
		$this->assertTrue($document->isPropertyModified('pInline'));

		$this->assertSame($id, $document->getPInline());

		$document->save();

		$documentManager->reset();

		/* @var $loadedDocument \Project\Tests\Documents\Inline */
		$loadedDocument = $documentManager->getDocumentInstance($document->getId());

		$this->assertNotSame($document, $loadedDocument);

		$id2 = $loadedDocument->getPInline();
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInline', $id2);
		$this->assertEquals('Test string', $id2->getTest());

		$this->assertFalse($loadedDocument->isPropertyModified('pInline'));


		$id2->setTest('Test 2');
		$this->assertTrue($loadedDocument->isPropertyModified('pInline'));

		$oldValue = $loadedDocument->getPInlineOldValue();
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInline', $oldValue);
		$this->assertEquals('Test string', $oldValue->getTest());

		$loadedDocument->save();

		$documentManager->reset();

		/* @var $reLoadedDocument \Project\Tests\Documents\Inline */
		$reLoadedDocument = $documentManager->getDocumentInstance($document->getId());
		$this->assertNotSame($loadedDocument, $reLoadedDocument);
		$this->assertEquals('Test 2', $reLoadedDocument->getPInline()->getTest());
	}


	public function testInlineArray()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();

		/* @var $document \Project\Tests\Documents\Inline */
		$document = $documentManager->getNewDocumentInstanceByModelName('Project_Tests_Inline');
		$this->assertInstanceOf('\Project\Tests\Documents\Inline', $document);
		$this->assertEquals('Project_Tests_Inline', $document->getDocumentModelName());

		$this->assertFalse($document->isPropertyModified('pInlineArray'));

		$arrayObject = $document->getPInlineArray();
		$this->assertInstanceOf('\Change\Documents\InlineArrayProperty', $arrayObject);
		$this->assertEquals(0, $arrayObject->count());

		/** @var $id \Project\Tests\Documents\InlinePInlineArray */
		$id = $document->newInlinePInlineArray();
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInlineArray', $id);
		$id->setTest('Test string');
		$id->setNumber(40);

		$arrayObject->add($id);

		$this->assertTrue($document->isPropertyModified('pInlineArray'));

		$document->save();

		$documentManager->reset();

		/* @var $loadedDocument \Project\Tests\Documents\Inline */
		$loadedDocument = $documentManager->getDocumentInstance($document->getId());

		$this->assertNotSame($document, $loadedDocument);

		$arrayObject = $loadedDocument->getPInlineArray();
		$this->assertInstanceOf('\Change\Documents\InlineArrayProperty', $arrayObject);
		$this->assertEquals(1, $arrayObject->count());

		$this->assertFalse($loadedDocument->isPropertyModified('pInline'));
		$id2 = $arrayObject[0];
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInlineArray', $id2);
		$this->assertEquals('Test string', $id2->getTest());
		$this->assertEquals(40, $id2->getNumber());


		$this->assertFalse($loadedDocument->isPropertyModified('pInlineArray'));

		$id2->setNumber(70);
		$this->assertTrue($loadedDocument->isPropertyModified('pInlineArray'));

		$array = $loadedDocument->getPInlineArrayOldValue();
		$this->assertCount(1, $array);
		$this->assertInstanceOf('\Project\Tests\Documents\InlinePInlineArray', $array[0]);
		$this->assertEquals(40, $array[0]->getNumber());

		$arrayObject->add($id);

		$loadedDocument->save();

		$documentManager->reset();

		/* @var $reLoadedDocument \Project\Tests\Documents\Inline */
		$reLoadedDocument = $documentManager->getDocumentInstance($document->getId());
		$this->assertNotSame($loadedDocument, $reLoadedDocument);
		$arrayObject = $reLoadedDocument->getPInlineArray();
		$this->assertEquals(2, $arrayObject->count());
		$this->assertEquals(70, $arrayObject[0]->getNumber());
		$this->assertEquals(40, $arrayObject[1]->getNumber());
	}
}
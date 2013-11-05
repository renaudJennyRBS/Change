<?php
namespace ChangeTests\Change\Documents;

class DocumentWeakReferenceTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testSerialize()
	{
		$mi = new \ChangeTests\Change\Documents\TestAssets\MemoryInstance();
		$manager = $this->getApplicationServices()->getDocumentManager();
		$document = $mi->getInstanceRo5001($manager);

		$id = $document->getId();
		$wr = new \Change\Documents\DocumentWeakReference($document);
		$this->assertEquals($id, $wr->getId());
		$this->assertEquals($document->getDocumentModelName(), $wr->getModelName());

		$this->assertSame($document, $wr->getDocument($manager));

		$serialized = serialize($wr);
		$wr2 = unserialize($serialized);
		$this->assertNotSame($wr, $wr2);

		$this->assertEquals($wr2->getId(), $wr->getId());
		$this->assertSame($document, $wr2->getDocument($manager));
	}
}
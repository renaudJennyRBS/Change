<?php
namespace ChangeTests\Change\Documents;

class DocumentWeakReferenceTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testInitializeDB()
	{
		$application = $this->getApplication();
		$compiler = new \Change\Documents\Generators\Compiler($application);
		$compiler->generate();
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testSerialize()
	{
		/* @var $testsBasicService\Project\Tests\Documents\BasicService */
		$testsBasicService = $this->getApplication()->getDocumentServices()->getProjectTestsBasic();

		$document = $testsBasicService->getInstanceRo5001();
		$manager = $document->getDocumentManager();

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
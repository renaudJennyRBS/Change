<?php


namespace ChangeTests\Change\Documents;

use Change\Presentation\Blocks\Parameters;

class RestfulDocumentTest extends \ChangeTests\Change\TestAssets\TestCase
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

	public function testPopulateDocumentFromRestEvent()
	{
		$data = ['model'=> 'toto', 'id' => 1, 'treeName' => 'foo', 'pStr' => 'a string', 'pBool' => true, 'pInt' => 10];


		/* @var $document \Project\Tests\Documents\Basic */
		$document = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$event = new \Change\Http\Event();
		$event->setRequest(new \Change\Http\Request());
		$event->getRequest()->setPost(new \Zend\Stdlib\Parameters($data));
		$event->setParams($this->getDefaultEventArguments());

		$result = $document->populateDocumentFromRestEvent($event);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $result);
		$this->assertEquals($data['pStr'], $document->getPStr());
		$this->assertEquals('Project_Tests_Basic', $document->getDocumentModel()->getName());
		$this->assertEquals('foo', $document->getTreeName());
		$this->assertEquals(true, $document->getPBool());
		$this->assertEquals(10, $document->getPInt());

		$document->save();

		/* @var $document \Project\Tests\Documents\Basic */
		$newDocument = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$result = $newDocument->populateDocumentFromRestEvent($event);
		$this->assertEquals(false, $result);
		$this->assertInstanceOf('\Change\Http\Rest\Result\ErrorResult', $event->getResult());
		$this->assertEquals(\Zend\Http\Response::STATUS_CODE_409, $event->getResult()->getHttpStatusCode());
		$this->assertEquals('DOCUMENT-ALREADY-EXIST', $event->getResult()->getErrorCode());

		unset($data['id']);
		$data['pStr'] = new \DateTime();
		$event->getRequest()->setPost(new \Zend\Stdlib\Parameters($data));
		$result = $newDocument->populateDocumentFromRestEvent($event);
		$this->assertEquals(false, $result);
		$this->assertInstanceOf('\Change\Http\Rest\Result\ErrorResult', $event->getResult());
		$this->assertEquals(\Zend\Http\Response::STATUS_CODE_409, $event->getResult()->getHttpStatusCode());
		$this->assertEquals('INVALID-VALUE-TYPE', $event->getResult()->getErrorCode());
		$this->assertEquals('pStr', $event->getResult()->getData()['name']);


		unset($data['pStr']);
		$event = new \Change\Http\Event();
		$event->setRequest(new \Change\Http\Request());
		$event->getRequest()->setPost(new \Zend\Stdlib\Parameters($data));
		$event->setParams($this->getDefaultEventArguments());

		$newDocument = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$eventManager = $this->getEventManagerFactory()->getSharedEventManager();
		$eventManager->attach('Project_Tests_Basic', 'populateDocumentFromRestEvent', function(\Change\Documents\Events\Event $event){
			$document = $event->getDocument();
			$this->assertNull($document->getPStr());
			$document->setPStr('tutu');
		}, 5);

		$result = $newDocument->populateDocumentFromRestEvent($event);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $result);
		$this->assertEquals('tutu', $newDocument->getPStr());
	}
}
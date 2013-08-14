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
	{/*
 * 		<property name="pStr" type="String" required="true"/>
		<property name="pBool" type="Boolean" />
		<property name="pInt" type="Integer" />
		<property name="pFloat" type="Float" />
		<property name="pDec" type="Decimal" />
		<property name="pDa" type="Date" />
		<property name="pDaTi" type="DateTime" />
		<property name="pText" type="LongString" />
		<property name="pJson" type="JSON" />
		<property name="pXml" type="XML" />
		<property name="pRt" type="RichText" />
		<property name="plob" type="Lob" />
		<property name="pObj" type="Object" />
		<property name="pDocId" type="DocumentId" />
		<property name="pStorUri" type="StorageUri" />

		<property name="pDocInst" type="Document" document-type="Project_Tests_Localized" />
		<property name="pDocArr" type="DocumentArray" document-type="Project_Tests_Localized"/>
 */
		$data = ['model'=> 'toto', 'id' => 1, 'treeName' => 'foo', 'pStr' => 'a string', 'pBool' => true, 'pInt' => 10];
		$event = new \Change\Http\Event();
		$event->setRequest(new \Change\Http\Request());
		$event->getRequest()->setPost(new \Zend\Stdlib\Parameters($data));

		/* @var $document \Project\Tests\Documents\Basic */
		$document = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$result = $document->populateDocumentFromRestEvent($event);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $result);
		$this->assertEquals($data['pStr'], $document->getPStr());
		$this->assertEquals('Project_Tests_Basic', $document->getDocumentModel()->getName());
		$this->assertEquals('foo', $document->getTreeName());
		$this->assertEquals(true, $document->getPBool());
		$this->assertEquals(10, $document->getPInt());

		$document->save();

		/* @var $document \Project\Tests\Documents\Basic */
		$newDocument = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
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

		$newDocument = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$eventManager = $this->getApplication()->getSharedEventManager();
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
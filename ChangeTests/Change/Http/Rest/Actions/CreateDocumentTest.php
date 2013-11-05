<?php
namespace ChangeTests\Change\Http\Rest\Actions;

use Change\Http\Rest\Actions\CreateDocument;

/**
* @name \ChangeTests\Change\Http\Rest\Actions\CreateDocumentTest
*/
class CreateDocumentTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}


	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}


	protected function getHttpEvent()
	{
		$event = new \Change\Http\Event();
		$event->setParams($this->getDefaultEventArguments());
		$uri = new \Zend\Uri\Http('http://localhost/rest.php');
		$event->setRequest(new \Change\Http\Request());
		$event->setUrlManager(new \Change\Http\UrlManager($uri, '/rest.php'));

		return $event;
	}

	public function testExecute()
	{
		//documentId, modelName
		$action = new CreateDocument();

		$event = $this->getHttpEvent();
		$event->setParam('modelName', 'Project_Tests_Basic');
		$event->getRequest()->getPost()->set('pStr', 'test');
		$action->execute($event);
		$id = $event->getParam('documentId', -1);
		$this->assertGreaterThan(0,	$id);

		/* @var $result \Change\Http\Rest\Result\DocumentResult */
		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Rest\Result\DocumentResult', $result);

		$this->assertEquals(201,	$result->getHttpStatusCode());
		$array = $result->toArray();


		$this->assertArrayHasKey('properties', $array);
		$this->assertArrayHasKey('id', $array['properties']);
		$this->assertEquals($id, $array['properties']['id']);

		$this->assertArrayHasKey('links', $array);
		$self = $array['links'][0];
		$model = $array['links'][1];
		$this->assertEquals('http://localhost/rest.php/models/Project/Tests/Basic', $model['href']);
		$this->assertEquals('http://localhost/rest.php/resources/Project/Tests/Basic/' . $id, $self['href']);
	}
}
<?php
namespace ChangeTests\Change\Http;

use Change\Http\UrlManager;

class UrlManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testConstruct()
	{
		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net');
		
		$urlManager = new UrlManager($uri);
		$this->assertInstanceOf('\Change\Http\UrlManager', $urlManager);

		$this->assertEquals('http://domain.net/', $urlManager->getSelf()->normalize()->toString());
	}

	/**
	 * @depends testConstruct
	 */
	public function testByPathInfo()
	{
		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net');
		$urlManager = new UrlManager($uri);

		$http = $urlManager->getByPathInfo('/test');
		$this->assertEquals('http://domain.net/test', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('test');
		$this->assertEquals('http://domain.net/test', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('');
		$this->assertEquals('http://domain.net/', $http->normalize()->toString());

		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net/home.html');
		$urlManager = new UrlManager($uri);

		$http = $urlManager->getByPathInfo('/test');
		$this->assertEquals('http://domain.net/test', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('test');
		$this->assertEquals('http://domain.net/test', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('');
		$this->assertEquals('http://domain.net/', $http->normalize()->toString());

		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net/home.html');
		$urlManager = new UrlManager($uri, 'index.php');

		$http = $urlManager->getByPathInfo('/test');
		$this->assertEquals('http://domain.net/index.php/test', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('');
		$this->assertEquals('http://domain.net/index.php/', $http->normalize()->toString());

		$http = $urlManager->getByPathInfo('', array('a' => ' b'));
		$this->assertEquals('http://domain.net/index.php/?a=%20b', $http->normalize()->toString());
	}
}

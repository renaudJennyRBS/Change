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

	/**
	 * @depends testConstruct
	 */
	public function testGetDefaultByDocument()
	{
		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net');
		$urlManager = new UrlManager($uri);

		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $doc \Project\Tests\Documents\Correction */
		$doc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$doc->initialize(1002);

		$this->assertNull($urlManager->getDefaultByDocument($doc)); // No publication section.

		$section = new UrlManagerTest_FakeSection(1001, new UrlManagerTest_FakeWebsite());
		$doc->setPublicationSections(array($section));
		$url = $urlManager->getDefaultByDocument($doc)->normalize()->toString();
		$this->assertStringStartsWith('http://domain.net/index.php/fr/', $url);
		$this->assertStringEndsWith(',1002.html', $url);
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetContextualByDocument()
	{
		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net');
		$urlManager = new UrlManager($uri);

		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $doc \Project\Tests\Documents\Correction */
		$doc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$doc->initialize(1002);

		$section = new UrlManagerTest_FakeSection(1001, new UrlManagerTest_FakeWebsite());

		$url = $urlManager->getContextualByDocument($doc, $section)->normalize()->toString();
		$this->assertStringStartsWith('http://domain.net/index.php/fr/', $url);
		$this->assertStringEndsWith(',1001,1002.html', $url);
	}
}

class UrlManagerTest_FakeSection implements \Change\Presentation\Interfaces\Section
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var \Change\Presentation\Interfaces\Website $website
	 */
	protected $website;


	protected $sectionPath = array();


	protected $title = 'UrlManagerTest_FakeSection';

	/**
	 * @param integer $id
	 * @param \Change\Presentation\Interfaces\Website $website
	 */
	public function __construct($id, $website)
	{
		$this->id = $id;
		$this->website = $website;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite()
	{
		return $this->website;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getSectionPath()
	{
		return $this->sectionPath;
	}
}

class UrlManagerTest_FakeWebsite implements \Change\Presentation\Interfaces\Website
{
	/**
	 * @return integer
	 */
	public function getId()
	{
		return 1000;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return 'fr_FR';
	}

	/**
	 * @return string
	 */
	public function getHostName()
	{
		return 'domain.net';
	}

	/**
	 * @return integer
	 */
	public function getPort()
	{
		return null;
	}

	/**
	 * @return string
	 */
	public function getScriptName()
	{
		return '/index.php';
	}

	/**
	 * Returned string do not start and end with '/' char
	 * @return string|null
	 */
	public function getRelativePath()
	{
		return 'fr';
	}
}
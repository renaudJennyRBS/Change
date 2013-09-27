<?php
namespace ChangeTests\Change\Http\Web;

use Change\Http\Web\PathRule;
use Change\Http\Web\PathRuleManager;

class UrlManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDb();
		static::initDocumentsClasses();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @param string $baseURL
	 * @return \Change\Http\Web\UrlManager
	 */
	protected function getObject($baseURL = 'http://domain.net')
	{
		$urlManager = new \Change\Http\Web\UrlManager(new \Zend\Uri\Http($baseURL));
		$urlManager->setApplicationServices($this->getApplicationServices());
		return $urlManager;
	}

	public function testWebsite()
	{
		$website = new FakeWebsite_5842135();
		$urlManager = $this->getObject();
		$this->assertNull($urlManager->getWebsite());
		$this->assertNull($urlManager->getLCID());
		$this->assertSame($urlManager, $urlManager->setWebsite($website));
		$this->assertSame($urlManager->getWebsite(), $website);
		$this->assertEquals($website->getLCID(), $urlManager->getLCID());
	}


	public function testLCID()
	{
		$urlManager = $this->getObject();
		$this->assertNull($urlManager->getLCID());
		$this->assertSame($urlManager, $urlManager->setLCID('en_US'));
		$this->assertEquals('en_US', $urlManager->getLCID());
	}

	public function testAbsoluteUrl()
	{
		$urlManager = $this->getObject();
		$this->assertEquals('test', $urlManager->getByPathInfo('test')->toString());
		$this->assertEquals('http://domain.net:80/', $urlManager->getByPathInfo('')->toString());

		$this->assertSame($urlManager, $urlManager->setAbsoluteUrl(true));
		$this->assertEquals('http://domain.net:80/test', $urlManager->getByPathInfo('test')->toString());
	}

	public function testGetByPathInfoForWebsite()
	{
		$website1 = new FakeWebsite_5842135();
		$urlManager = $this->getObject();
		$urlManager->setWebsite($website1);
		$uri = $urlManager->getByPathInfoForWebsite($website1, $website1->getLCID(), 'test.html', array('a' => 'b'));
		$this->assertEquals('test.html?a=b', $uri->toString());
		$urlManager->setAbsoluteUrl(true);
		$uri = $urlManager->getByPathInfoForWebsite($website1, $website1->getLCID(), 'test.html', array('a' => 'b'));
		$this->assertEquals('http://domain.net:80/test.html?a=b', $uri->toString());

		$urlManager->setAbsoluteUrl(false);
		$website2 = new FakeWebsite_5842135();
		$website2->id = 1001;
		$website2->LCID = 'en_US';
		$website2->hostName = 'website2.domain.net';
		$website2->port = 8080;
		$uri = $urlManager->getByPathInfoForWebsite($website2, $website2->getLCID(), 'test.html', array('a' => 'b'));
		$this->assertEquals('http://website2.domain.net:8080/index.php/fr/test.html?a=b', $uri->toString());
	}

	public function testCanonicalByDocument()
	{
		$website1 = new FakeWebsite_5842135();
		$urlManager = $this->getObject();
		$urlManager->setWebsite($website1);

		$this->assertEquals('document/500.html', $urlManager->getCanonicalByDocument(500)->toString());
	}

	public function testGetByDocument()
	{
		$website1 = new FakeWebsite_5842135();
		$section1 = new FakeSection_5842135(2000, $website1);

		$urlManager = $this->getObject();
		$urlManager->setWebsite($website1);
		$document = $this->getNewReadonlyDocument('Project_Tests_Correction', 3000);

		$uri = $urlManager->getByDocument($document, $section1, array('a' => 'b'));
		$this->assertEquals('document/2000/3000.html?a=b', $uri->toString());


		$website2 = new FakeWebsite_5842135();
		$website2->id = 1001;
		$website2->LCID = 'en_US';
		$website2->hostName = 'website2.domain.net';
		$website2->port = 8080;

		$section2 =  new FakeSection_5842135(2001, $website2);

		$uri = $urlManager->getByDocument($document, $section2, array('a' => 'b'));
		$this->assertEquals('http://website2.domain.net:8080/index.php/fr/document/2001/3000.html?a=b', $uri->toString());
	}

	public function testPathRules()
	{
		$pathRule = new PathRule();
		$pathRule->setWebsiteId(1000)->setLCID('fr_FR')
			->setDocumentId(3000)->setRelativePath('testDoc3.html')->setQuery(null)
			->setHttpStatus(200);
		$this->insertPathRule($this->getApplicationServices(), $pathRule);

		$pathRule = new PathRule();
		$pathRule->setWebsiteId(1000)->setLCID('fr_FR')->setSectionId(2000)
			->setDocumentId(3000)->setRelativePath('2000/testDoc3.html')->setQuery(null)
			->setHttpStatus(200);
		$this->insertPathRule($this->getApplicationServices(), $pathRule);

		$pathRule = new PathRule();
		$pathRule->setWebsiteId(1000)->setLCID('fr_FR')
			->setDocumentId(3001)->setRelativePath('testA1.html')->setQuery('a=1&c=8')
			->setHttpStatus(200);
		$this->insertPathRule($this->getApplicationServices(), $pathRule);

		$pathRule = new PathRule();
		$pathRule->setWebsiteId(1000)->setLCID('fr_FR')
			->setDocumentId(3001)->setRelativePath('testA2.html')->setQuery('a=2&c=8')
			->setHttpStatus(200);
		$this->insertPathRule($this->getApplicationServices(), $pathRule);

		$urlManager = $this->getObject();
		$website1 = new FakeWebsite_5842135();
		$urlManager->setWebsite($website1);

		$section1 = new FakeSection_5842135(2000, $website1);
		$section2 = new FakeSection_5842135(2001, $website1);

		$document3000 = $this->getNewReadonlyDocument('Project_Tests_Correction', 3000);

		$uri = $urlManager->getCanonicalByDocument($document3000, $website1, array('b' => 10));
		$this->assertEquals('testDoc3.html?b=10', $uri->toString());

		$uri = $urlManager->getByDocument($document3000, $section1, array('b' => 10));
		$this->assertEquals('2000/testDoc3.html?b=10', $uri->toString());

		$uri = $urlManager->getByDocument($document3000, $section2, array('b' => 10));
		$this->assertEquals('document/2001/3000.html?b=10', $uri->toString());

		$document3001 = $this->getNewReadonlyDocument('Project_Tests_Correction', 3001);

		$uri = $urlManager->getCanonicalByDocument($document3001, $website1, array('b' => 10));
		$this->assertEquals('document/3001.html?b=10', $uri->toString());

		$callback =  function(\Change\Documents\Events\Event $event)
		{
			/* @var $queryParameters \ArrayObject */
			$queryParameters = $event->getParam('queryParameters');

			/* @var $pathRules \Change\Http\Web\PathRule[] */
			$pathRules = $event->getParam('pathRules');
			$pathRule = $queryParameters['b'] == 10 ? $pathRules[0] : $pathRules[1];

			$queryParameters['count'] = count($pathRules);

			foreach ($pathRule->getQueryParameters() as $k => $v)
			{
				$queryParameters[$k] = $v;
			}

			$event->setParam('pathRule', $pathRule);
		};

		$document3001->getEventManager()->attach('selectPathRule', $callback);

		$uri = $urlManager->getCanonicalByDocument($document3001, $website1, array('b' => 10));
		$this->assertEquals('testA1.html?b=10&count=2&a=1&c=8', $uri->toString());

		$uri = $urlManager->getCanonicalByDocument($document3001, $website1, array('b' => 12));
		$this->assertEquals('testA2.html?b=12&count=2&a=2&c=8', $uri->toString());
	}

	public function testRewritePathRule()
	{
		$prm = new PathRuleManager($this->getApplicationServices());
		$rule = $prm->getNewRule(1000, 'fr_FR', 'test.html', 4000, 200);

		$document = $this->getNewReadonlyDocument('Project_Tests_Correction', 4000);
		$callback = function ($event) {
			$pathRule = $event->getParam('pathRule');
			$pathRule->setRelativePath('toto.html');
		};

		$document->getEventManager()->attach('populatePathRule', $callback);
		$urlManager = $this->getObject();
		$pathRule = $urlManager->rewritePathRule($document, $rule);
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule);
		$this->assertEquals('toto.html', $pathRule->getRelativePath());

		$document->getEventManager()->attach('populatePathRule', $callback);
		$pathRule = $urlManager->rewritePathRule($document, $rule);
		$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule);
		$this->assertEquals('4000/toto.html', $pathRule->getRelativePath());

		try
		{
			$pathRule = $urlManager->rewritePathRule($document, $rule);
			$this->assertInstanceOf('\Change\Http\Web\PathRule', $pathRule);
			$this->fail('Exception expected!');
		}
		catch (\Exception $e) { }
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param PathRule $pathRule
	 */
	protected function insertPathRule($applicationServices, $pathRule)
	{
		$applicationServices->getTransactionManager()->begin();
		$prm = new PathRuleManager($applicationServices);
		$prm->insertPathRule($pathRule);
		$applicationServices->getTransactionManager()->commit();
	}
}

class FakeSection_5842135 implements \Change\Presentation\Interfaces\Section
{
	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var \Change\Presentation\Interfaces\Website $website
	 */
	public $website;

	public $sectionPath = array();

	public $title = 'FakeSection_5842135';

	/**
	 * @param integer $id
	 * @param \Change\Presentation\Interfaces\Website $website
	 */
	public function __construct($id, $website)
	{
		$this->id = $id;
		$this->website = $website;
		$this->sectionPath = array($website);
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
	 * @return string
	 */
	public function getPathPart()
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

class FakeWebsite_5842135 implements \Change\Presentation\Interfaces\Website
{
	public $id = 1000;

	public $LCID = 'fr_FR';
	
	public $hostName = 'domain.net';
	
	public $scriptName = '/index.php';

	public $relativePath = 'fr';

	public $port = null;
	
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @return string
	 */
	public function getHostName()
	{
		return $this->hostName;
	}

	/**
	 * @return integer
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @return string
	 */
	public function getScriptName()
	{
		return $this->scriptName;
	}

	/**
	 * Returned string do not start and end with '/' char
	 * @return string|null
	 */
	public function getRelativePath()
	{
		return $this->relativePath;
	}

	/**
	 * @return string
	 */
	public function getBaseurl()
	{
		return $this->getUrlManager($this->getLCID())->getBaseUri()->normalize()->toString();
	}

	/**
	 * @param string $LCID
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager($LCID)
	{
		$url = new \Zend\Uri\Http();
		$url->setScheme("http");
		$url->setHost($this->getHostName());
		$url->setPort($this->getPort());
		$url->setPath('/');
		$urlManager = new \Change\Http\Web\UrlManager($url, $this->getScriptName());
		$urlManager->setBasePath($this->getRelativePath());
		$urlManager->setAbsoluteUrl(true);
		return $urlManager;
	}
}
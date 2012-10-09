<?php
namespace Tests\Change\Configuration;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
	public function testInitDomDocument()
	{
		$generator = new \Tests\Change\Configuration\Generator();
		
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project1.xml';
		$dom = $generator->initDomDocument($filePath);
		$xpath = new \DOMXpath($dom);
		
		$this->assertEquals(0, $xpath->query("/project/config/modules/website/entry")->length);
		$this->assertEquals(0, $xpath->query("/project/config/modules/website/entry[@name='highlighter']")->length);
		$this->assertEquals(0, $xpath->query("/project/config/modules/website/entry[@name='forms-use-qtip-help']")->length);
		$this->assertEquals(4, $xpath->query("/project/config/modules/website/sample/entry")->length);
		$this->assertEquals('default/sidebarpageecomsample', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPageTemplate']")->item(0)->textContent);
		$this->assertEquals('default/popin', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPopinTemplate']")->item(0)->textContent);
		
		$this->assertEquals(1, $xpath->query("/project/config/modules/catalog/entry")->length);
		$this->assertEquals(0, $xpath->query("/project/config/modules/catalog/modulesCatalogProductSuggestionFeeder/entry")->length);
		$this->assertEquals('sample_BillingAreaSelectorStrategy', $xpath->query("/project/config/modules/catalog/entry[@name='currentBillingAreaStrategyClass']")->item(0)->textContent);
		
		$this->assertEquals(3, $xpath->query("/project/config/tal/prefix/entry")->length);
		$this->assertEquals('website_TalesUrl', $xpath->query("/project/config/tal/prefix/entry[@name='tagurl']")->item(0)->textContent);
		$this->assertEquals(0, $xpath->query("/project/config/tal/prefix/entry[@name='currenturl']")->length);
		
		$this->assertEquals(0, $xpath->query("/project/config/injection")->length);
		$this->assertEquals(0, $xpath->query("/project/config/injection/class/entry")->length);
		$this->assertEquals(0, $xpath->query("/project/config/injection/class/entry[@name='change_Controller']")->length);
		
		$this->assertEquals(0, $xpath->query("/project/config/packageversion")->length);
		
		return $dom;
	}
	
	/**
	 * @depends testInitDomDocument
	 */
	public function testMergeProjectFile(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project2.xml';
		$generator->mergeProjectFile($dom, $filePath);
		$xpath = new \DOMXpath($dom);
		
		$this->assertEquals(0, $xpath->query("/project/config/modules/website/entry")->length);
		$this->assertEquals(4, $xpath->query("/project/config/modules/website/sample/entry")->length);
		$this->assertEquals('montheme/sidebarpageecomsample', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPageTemplate']")->item(0)->textContent);
		$this->assertEquals('default/popin', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPopinTemplate']")->item(0)->textContent);
		
		$this->assertEquals(1, $xpath->query("/project/config/modules/catalog/entry")->length);
		$this->assertEquals(0, $xpath->query("/project/config/modules/catalog/modulesCatalogProductSuggestionFeeder/entry")->length);
		$this->assertEquals('sample_BillingAreaSelectorStrategy', $xpath->query("/project/config/modules/catalog/entry[@name='currentBillingAreaStrategyClass']")->item(0)->textContent);
		
		$this->assertEquals(5, $xpath->query("/project/config/tal/prefix/entry")->length);
		$this->assertEquals('website_TalesUrl2', $xpath->query("/project/config/tal/prefix/entry[@name='tagurl']")->item(0)->textContent);
		$this->assertEquals('website_TalesUrl', $xpath->query("/project/config/tal/prefix/entry[@name='currenturl']")->item(0)->textContent);
		
		$this->assertEquals(1, $xpath->query("/project/config/injection")->length);
		$this->assertEquals(2, $xpath->query("/project/config/injection/class/entry")->length);
		$this->assertEquals('users_ChangeController', $xpath->query("/project/config/injection/class/entry[@name='change_Controller']")->item(0)->textContent);
		
		//echo $dom->saveXML($dom), PHP_EOL;
	}
	
	/**
	 * @depends testInitDomDocument
	 */
	public function testMergeModuleFile(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		$xpath = new \DOMXpath($dom);
		
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'module1.xml';
		$generator->mergeModuleFile($dom, 'website', $filePath);
		
		$this->assertEquals(3, $xpath->query("/project/config/modules/website/entry")->length);
		$this->assertEquals('website_MinimalHighlighter', $xpath->query("/project/config/modules/website/entry[@name='highlighter']")->item(0)->textContent);
		$this->assertEquals('false', $xpath->query("/project/config/modules/website/entry[@name='forms-use-qtip-help']")->item(0)->textContent);
		$this->assertEquals(4, $xpath->query("/project/config/modules/website/sample/entry")->length);
		$this->assertEquals('default/sidebarpage', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPageTemplate']")->item(0)->textContent);
		$this->assertEquals('default/popin', $xpath->query("/project/config/modules/website/sample/entry[@name='defaultPopinTemplate']")->item(0)->textContent);
		
		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos")->length);
		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos/website")->length);
		$this->assertEquals('true', $xpath->query("/project/config/modulesinfos/website/visible")->item(0)->textContent);
		$this->assertEquals('hot', $xpath->query("/project/config/modulesinfos/website/category")->item(0)->textContent);
		
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'module2.xml';
		$generator->mergeModuleFile($dom, 'catalog', $filePath);
		
		$this->assertEquals(3, $xpath->query("/project/config/modules/website/entry")->length);
		$this->assertEquals('website_MinimalHighlighter', $xpath->query("/project/config/modules/website/entry[@name='highlighter']")->item(0)->textContent);
		$this->assertEquals('true', $xpath->query("/project/config/modules/website/entry[@name='forms-use-qtip-help']")->item(0)->textContent);
		
		$this->assertEquals(3, $xpath->query("/project/config/modules/catalog/entry")->length);
		$this->assertEquals('sample_BillingAreaSelectorStrategy', $xpath->query("/project/config/modules/catalog/entry[@name='currentBillingAreaStrategyClass']")->item(0)->textContent);
		$this->assertEquals('100', $xpath->query("/project/config/modules/catalog/entry[@name='compilationChunkSize']")->item(0)->textContent);
		$this->assertEquals(2, $xpath->query("/project/config/modules/catalog/modulesCatalogProductSuggestionFeeder/entry")->length);
		$this->assertEquals('catalog_SameShelvesProductFeeder', $xpath->query("/project/config/modules/catalog/modulesCatalogProductSuggestionFeeder/entry[@name='1']")->item(0)->textContent);
		
		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos")->length);
		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos/website")->length);
		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos/catalog")->length);
		$this->assertEquals('true', $xpath->query("/project/config/modulesinfos/catalog/visible")->item(0)->textContent);
		$this->assertEquals('e-commerce', $xpath->query("/project/config/modulesinfos/catalog/category")->item(0)->textContent);
		
		return $dom;
	}
	
	/**
	 * @depends testMergeModuleFile
	 */
	public function testMergeInstallFile(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		$xpath = new \DOMXpath($dom);
		
		$this->assertEquals(0, $xpath->query("/project/config/modulesinfos/website/version")->length);
		
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'install1.xml';
		$generator->mergeInstallFile($dom, 'website', $filePath);

		$this->assertEquals(1, $xpath->query("/project/config/modulesinfos/website/version")->length);
		$this->assertEquals('4.0.0', $xpath->query("/project/config/modulesinfos/website/version")->item(0)->textContent);
	}
	
	/**
	 * @depends testInitDomDocument
	 */
	public function testSetNodeValue(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		$xpath = new \DOMXpath($dom);
		
		$this->assertEquals(1, $xpath->query("/project/config/browsers/frontoffice/firefox/version")->length);
		$node = $xpath->query("/project/config/browsers/frontoffice/firefox/version[@name='0']")->item(0);
		$this->assertEquals('12.0', $node->textContent);
		
		$generator->setNodeValue($node, '14.0');

		$this->assertEquals(1, $xpath->query("/project/config/browsers/frontoffice/firefox/version")->length);
		$node = $xpath->query("/project/config/browsers/frontoffice/firefox/version[@name='0']")->item(0);
		$this->assertEquals('14.0', $node->textContent);
	}
	
	/**
	 * @depends testInitDomDocument
	 */
	public function testSetDefine(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		$xpath = new \DOMXpath($dom);
		
		$this->assertEquals(0, $xpath->query("/project/defines/define")->length);
		
		$generator->setDefine($dom, 'TOTO', 'toto1');
		
		$this->assertEquals(1, $xpath->query("/project/defines/define")->length);
		$this->assertEquals('toto1', $xpath->query("/project/defines/define[@name='TOTO']")->item(0)->textContent);
		
		$generator->setDefine($dom, 'TITI', 'titi1');
		
		$this->assertEquals(2, $xpath->query("/project/defines/define")->length);
		$this->assertEquals('toto1', $xpath->query("/project/defines/define[@name='TOTO']")->item(0)->textContent);
		$this->assertEquals('titi1', $xpath->query("/project/defines/define[@name='TITI']")->item(0)->textContent);
		
		$generator->setDefine($dom, 'TOTO', 'toto2');
		
		$this->assertEquals(2, $xpath->query("/project/defines/define")->length);
		$this->assertEquals('toto2', $xpath->query("/project/defines/define[@name='TOTO']")->item(0)->textContent);
		$this->assertEquals('titi1', $xpath->query("/project/defines/define[@name='TITI']")->item(0)->textContent);
		
		return $dom;
	}
	
	/**
	 * @depends testSetDefine
	 */
	public function testCompileDefines(\DOMDocument $dom)
	{
		$generator = new \Tests\Change\Configuration\Generator();
		
		$defines = $generator->compileDefines($dom);
		
		$this->assertCount(2, $defines);
		$this->assertEquals('toto2', $defines['TOTO']);
		$this->assertEquals('titi1', $defines['TITI']);
	}
}

/**
 * Make some protected methods public for test.
 */
class Generator extends \Change\Configuration\Generator
{
	/**
	 * @param string $filePath
	 * @return \DOMDocument
	 */
	public function initDomDocument($filePath)
	{
		return parent::initDomDocument($filePath);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $moduleName
	 * @param string $filePath
	 */
	public function mergeModuleFile($dom, $moduleName, $filePath)
	{
		parent::mergeModuleFile($dom, $moduleName, $filePath);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $moduleName
	 * @param string $filePath
	 */
	public function mergeInstallFile($dom, $moduleName, $filePath)
	{
		parent::mergeInstallFile($dom, $moduleName, $filePath);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $filePath
	 */
	public function mergeProjectFile($dom, $filePath)
	{
		parent::mergeProjectFile($dom, $filePath);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @return array
	 */
	public function compileDefines($dom)
	{
		return parent::compileDefines($dom);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $name
	 * @param string $value
	 */
	public function setDefine($dom, $name, $value)
	{
		parent::setDefine($dom, $name, $value);
	}

	/**
	 * @param \DOMElement $node
	 * @param string $value
	 */
	public function setNodeValue($node, $value)
	{
		parent::setNodeValue($node, $value);
	}
}
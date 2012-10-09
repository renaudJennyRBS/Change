<?php
namespace Tests\Change\Configuration\Configuration;

class ArrayCompilerTest extends \PHPUnit_Framework_TestCase
{
	public function testInitDomDocument()
	{
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project3.xml';
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->load($filePath);

		$xpath = new \DOMXpath($dom);
		$nodeList = $xpath->query('/project/config');
		
		$compiler = new \Change\Configuration\ArrayCompiler();
		$array = $compiler->getConfigurationArray($nodeList->item(0));
		
		$this->assertCount(2, $array['config']['modules']);
		$this->assertEquals('sample_BillingAreaSelectorStrategy', $array['config']['modules']['catalog']['currentBillingAreaStrategyClass']);
		$this->assertCount(2, $array['config']['modules']['catalog']['modulesCatalogProductSuggestionFeeder']);
		$this->assertCount(4, $array['config']['modules']['website']['sample']);
		$this->assertEquals('default/nosidebarpage', $array['config']['modules']['website']['sample']['defaultHomeTemplate']);
		$this->assertCount(3, $array['config']['browsers']['frontoffice']['ie']);
		$this->assertEquals('8.0', $array['config']['browsers']['frontoffice']['ie'][0]);
		
		return $dom;
	}
}
<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\TrimTest
 */
class TrimTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Trim($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('Toto', array()));
		$this->assertFalse($converter->isEmptyFromUI(' Toto', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Trim($i18n);

		$this->assertEquals('test', $converter->parseFromUI('test', array()));
		$this->assertEquals('test', $converter->parseFromUI('   test  ', array()));
		$this->assertTrue($converter->parseFromUI(array(), array()) instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\TextTest
 */
class TextTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Text($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('Toto', array()));
		$this->assertFalse($converter->isEmptyFromUI(' Toto', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Text($i18n);

		$this->assertEquals('test', $converter->parseFromUI('test', array()));
		$this->assertEquals('test', $converter->parseFromUI('   test  ', array()));
		$this->assertEquals('test', $converter->parseFromUI('   test  ', array('minSize' => 4)));
		$this->assertError($converter->parseFromUI('   test  ', array('minSize' => 5)));
		$this->assertEquals('test', $converter->parseFromUI('   test  ', array('maxSize' => 4)));
		$this->assertError($converter->parseFromUI('   test  ', array('maxSize' => 2)));
		$this->assertEquals('A7', $converter->parseFromUI('   A7  ', array('pattern' => '^[A-Z][0-9]$')));
		$this->assertError($converter->parseFromUI('A7 zqf qfz77', array('pattern' => '^[A-Z][0-9]$')));
		$this->assertError($converter->parseFromUI('a7', array('pattern' => '^[A-Z][0-9]$')));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
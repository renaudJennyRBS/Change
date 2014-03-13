<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\DateTest
 */
class DateTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Date($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('Toto', array()));
		$this->assertFalse($converter->isEmptyFromUI(' Toto', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Date($i18n);

		$this->assertEquals('2013-12-24', $converter->parseFromUI('2013-12-24', array()));
		$this->assertEquals('2013-12-24', $converter->parseFromUI('   2013-12-24  ', array()));
		$this->assertError($converter->parseFromUI('abc', array()));
		$this->assertError($converter->parseFromUI('2013-13-24', array()));
		$this->assertError($converter->parseFromUI('2013-12-32', array()));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
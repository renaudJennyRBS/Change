<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\TrimArrayTest
 */
class TrimArrayTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\TrimArray($i18n);

		$this->assertTrue($converter->isEmptyFromUI(array(), array()));
		$this->assertTrue($converter->isEmptyFromUI(array('    '), array()));
		$this->assertFalse($converter->isEmptyFromUI(array('Toto'), array()));
		// If the value is not an array, the value is considered as empty.
		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('Toto', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\TrimArray($i18n);

		$this->assertError($converter->parseFromUI(null, array()));
		$this->assertError($converter->parseFromUI('', array()));
		$this->assertError($converter->parseFromUI('test', array()));
		$this->assertEquals(array('test'), $converter->parseFromUI(array('test'), array()));
		// Empty values are removed.
		$this->assertEquals(array('test', 'toto'), $converter->parseFromUI(array('   ', 'test', 'toto    '), array()));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
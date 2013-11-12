<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\BooleanTest
 */
class BooleanTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Boolean($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('true', array()));
		$this->assertFalse($converter->isEmptyFromUI(' false', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Boolean($i18n);

		$this->assertTrue($converter->parseFromUI('true', array()));
		$this->assertTrue($converter->parseFromUI('		true  ', array()));
		$this->assertFalse($converter->parseFromUI('false', array()));
		$this->assertFalse($converter->parseFromUI('		false  ', array()));
		$this->assertError($converter->parseFromUI('FALSE', array()));
		$this->assertError($converter->parseFromUI('1', array()));
		$this->assertError($converter->parseFromUI('toto', array()));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
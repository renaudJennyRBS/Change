<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\FloatTest
 */
class FloatTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Float($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('85.54', array()));
		$this->assertFalse($converter->isEmptyFromUI(' 36.36', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Float($i18n);

		$this->assertEquals(6, $converter->parseFromUI('6', array()));
		$this->assertEquals(8, $converter->parseFromUI('		8  ', array()));
		$this->assertEquals(-72.53, $converter->parseFromUI('-72.53', array()));
		$this->assertEquals(89.24, $converter->parseFromUI('		89.24  ', array()));

		// Invalid formats.
		$this->assertError($converter->parseFromUI('89.4b', array()));
		$this->assertError($converter->parseFromUI('n56', array()));
		$this->assertError($converter->parseFromUI('85v6.3', array()));

		// Handle locales.
		$i18n->setLCID('fr_FR');
		$this->assertEquals(895546423, $converter->parseFromUI('895 546 423', array()));
		$this->assertEquals(-999555666, $converter->parseFromUI('		-999 555 666  ', array()));
		$this->assertEquals(5546423.56, $converter->parseFromUI('5 546 423,56', array()));
		$this->assertEquals(-9555666.98, $converter->parseFromUI('		-9 555 666,98  ', array()));

		$i18n->setLCID('en_US');
		$this->assertEquals(895546423, $converter->parseFromUI('895,546,423', array()));
		$this->assertEquals(-999555666, $converter->parseFromUI('		-999,555,666  ', array()));
		$this->assertEquals(5546423.35, $converter->parseFromUI('5,546,423.35', array()));
		$this->assertEquals(-9555666.96, $converter->parseFromUI('		-9,555,666.96  ', array()));

		// Validators.
		$this->assertEquals(41.99, $converter->parseFromUI('41.99', array('max' => 42)));
		$this->assertEquals(42, $converter->parseFromUI('42', array('max' => 42)));
		$this->assertError($converter->parseFromUI('42.01', array('max' => 42)));

		$this->assertError($converter->parseFromUI('41.99', array('min' => 42)));
		$this->assertEquals(42, $converter->parseFromUI('42', array('min' => 42)));
		$this->assertEquals(42.01, $converter->parseFromUI('42.01', array('min' => 42)));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
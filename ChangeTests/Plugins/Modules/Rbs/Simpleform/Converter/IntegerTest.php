<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\IntegerTest
 */
class IntegerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Integer($i18n);

		$this->assertTrue($converter->isEmptyFromUI(null, array()));
		$this->assertTrue($converter->isEmptyFromUI('', array()));
		$this->assertTrue($converter->isEmptyFromUI('  	  	 ', array()));
		$this->assertFalse($converter->isEmptyFromUI('85', array()));
		$this->assertFalse($converter->isEmptyFromUI(' 36', array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\Integer($i18n);

		$this->assertEquals(6, $converter->parseFromUI('6', array()));
		$this->assertEquals(8, $converter->parseFromUI('		8  ', array()));
		$this->assertEquals(-7253, $converter->parseFromUI('-7253', array()));
		$this->assertEquals(8924, $converter->parseFromUI('		8924  ', array()));

		// Invalid formats.
		$this->assertError($converter->parseFromUI('894b', array()));
		$this->assertError($converter->parseFromUI('n56', array()));
		$this->assertError($converter->parseFromUI('85v63', array()));

		// Handle locales.
		$i18n->setLCID('fr_FR');
		$this->assertEquals(895546423, $converter->parseFromUI('895 546 423', array()));
		$this->assertEquals(-999555666, $converter->parseFromUI('		-999 555 666  ', array()));

		$i18n->setLCID('en_US');
		$this->assertEquals(895546423, $converter->parseFromUI('895,546,423', array()));
		$this->assertEquals(-999555666, $converter->parseFromUI('		-999,555,666  ', array()));

		// Validators.
		$this->assertEquals(41, $converter->parseFromUI('41', array('max' => 42)));
		$this->assertEquals(42, $converter->parseFromUI('42', array('max' => 42)));
		$this->assertError($converter->parseFromUI('43', array('max' => 42)));

		$this->assertError($converter->parseFromUI('41', array('min' => 42)));
		$this->assertEquals(42, $converter->parseFromUI('42', array('min' => 42)));
		$this->assertEquals(43, $converter->parseFromUI('43', array('min' => 42)));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
<?php
namespace ChangeTests\Plugins\Modules\Simpleform\Converter;

/**
 * @name \ChangeTests\Plugins\Modules\Simpleform\Converter\DateTimeTest
 */
class DateTimeTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testIsEmptyFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\DateTime($i18n);

		$this->assertTrue($converter->isEmptyFromUI(array(), array()));
		$this->assertTrue($converter->isEmptyFromUI(array('date' => '', 'time' => ''), array()));
		$this->assertFalse($converter->isEmptyFromUI(array('date' => 'toto', 'time' => ''), array()));
		$this->assertFalse($converter->isEmptyFromUI(array('date' => 'toto', 'time' => 'test'), array()));
		$this->assertFalse($converter->isEmptyFromUI(array('date' => '', 'time' => 'test'), array()));
	}

	public function testParseFromUI()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		$converter = new \Rbs\Simpleform\Converter\DateTime($i18n);

		$this->assertError($converter->parseFromUI('2013-12-24', array()));
		$this->assertEquals('2013-12-24 12:12:00', $converter->parseFromUI(array('date' => '2013-12-24', 'time' => '12:12'), array()));
		$this->assertEquals('2013-12-24 12:12:01', $converter->parseFromUI(array('date' => '2013-12-24', 'time' => '12:12:01'), array()));
		$this->assertError($converter->parseFromUI(array('date' => '2013-12-24', 'time' => ''), array()));
		$this->assertError($converter->parseFromUI(array('date' => '', 'time' => '12:12'), array()));
		$this->assertError($converter->parseFromUI(array('date' => '2013-12-24', 'time' => '12:12a'), array()));
	}

	protected function assertError($value)
	{
		$this->assertTrue($value instanceof \Rbs\Simpleform\Converter\Validation\Error);
	}
}
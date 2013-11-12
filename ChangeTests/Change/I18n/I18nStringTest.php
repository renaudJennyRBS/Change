<?php
namespace ChangeTests\Change\I18n;

use Change\I18n\I18nString;

class I18nStringTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstructor()
	{
		$manager = $this->getApplicationServices()->getI18nManager();

		// Giving a string as key.

		$string0 = new I18nString($manager, '');
		$this->assertEquals('', $string0->getKey());
		$this->assertEquals(array(), $string0->getFormatters());
		$this->assertEquals(array(), $string0->getReplacements());

		$string1 = new I18nString($manager, 'm.website.fo.test');
		$this->assertEquals('m.website.fo.test', $string1->getKey());
		$this->assertEquals(array(), $string1->getFormatters());
		$this->assertEquals(array(), $string1->getReplacements());

		$string2 = new I18nString($manager, 'm.website.fo.test-params', array('ucf'), array('param1' => 'Value 1',
			'param2' => 'Value 2'));
		$this->assertEquals('m.website.fo.test-params', $string2->getKey());
		$this->assertEquals(array('ucf'), $string2->getFormatters());
		$this->assertEquals(array('param1' => 'Value 1', 'param2' => 'Value 2'), $string2->getReplacements());

		// Giving a PreparedKey.

		$key = new \Change\I18n\PreparedKey('m.website.fo.test-params', array('ucf'), array('param1' => 'Value 1',
			'param10' => 'Value 10'));

		$string3 = new I18nString($manager, $key);
		$this->assertEquals('m.website.fo.test-params', $string3->getKey());
		$this->assertEquals(array('ucf'), $string3->getFormatters());
		$this->assertEquals(array('param1' => 'Value 1', 'param10' => 'Value 10'),
			$string3->getReplacements());

		$string4 = new I18nString($manager, $key, array('ucf', 'lab'), array('param1' => 'Value 1 bis',
			'param2' => 'Value 2'));
		$this->assertEquals('m.website.fo.test-params', $string3->getKey());
		$this->assertEquals(array('ucf', 'lab'), $string4->getFormatters());
		$this->assertEquals(array('param1' => 'Value 1 bis', 'param2' => 'Value 2', 'param10' => 'Value 10'),
			$string4->getReplacements());
	}

	public function testToString()
	{
		$manager = $this->getApplicationServices()->getI18nManager();

		$this->assertEquals('fr_FR', $manager->getLCID());

		// Key translation.
		$this->assertEquals('plip fr b', strval(new I18nString($manager, 'm.project.tests.a.aa.plip')));

		// Converters.
		$this->assertEquals('Plop fr a.aa', strval(new I18nString($manager, 'm.project.tests.a.aa.plop', array('ucf'))));
		$this->assertEquals('PLOP FR A.AA :', strval(new I18nString($manager, 'm.project.tests.a.aa.plop', array('uc', 'lab'))));

		// Substitutions.
		$this->assertEquals('Withparams test {param2} fr a',
			strval(new I18nString($manager, 'm.project.tests.a.withparams', array('ucf'), array('param1' => 'test'))));
		$this->assertEquals('withparams test youpi fr a',
			strval(new I18nString($manager, 'm.project.tests.a.withparams', array(), array('param1' => 'test',
				'param2' => 'youpi'))));
	}
}
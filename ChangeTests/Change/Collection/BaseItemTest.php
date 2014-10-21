<?php
namespace ChangeTests\Change\Collection;

use Change\Collection\BaseItem;

/**
 * @name \ChangeTests\Change\Collection\BaseItemTest
 */
class BaseItemTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testAll()
	{
		// Basic cases...

		$item = new BaseItem('aa');
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('aa', $item->getLabel());
		$this->assertEquals('aa', $item->getTitle());

		$item = new BaseItem('aa', 'test');
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('test', $item->getLabel());
		$this->assertEquals('test', $item->getTitle());

		$item = new BaseItem('aa', 'test', 'toto');
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('test', $item->getLabel());
		$this->assertEquals('toto', $item->getTitle());

		// To string conversions...

		$item = new BaseItem('aa', new StringableObject1());
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('Change', $item->getLabel());
		$this->assertEquals('Change', $item->getTitle());

		$item = new BaseItem('aa', new StringableObject1(), new StringableObject2());
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('Change', $item->getLabel());
		$this->assertEquals('Chuck Norris', $item->getTitle());

		// Giving an array...

		$item = new BaseItem('aa', array('Youpi', new StringableObject1()));
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('Youpi', $item->getLabel());
		$this->assertEquals('Change', $item->getTitle());

		$item = new BaseItem('aa', array('label' => new StringableObject1(), 'title' => 'Hello'));
		$this->assertEquals('aa', $item->getValue());
		$this->assertEquals('Change', $item->getLabel());
		$this->assertEquals('Hello', $item->getTitle());
	}
}

class StringableObject1
{
	public function __toString()
	{
		return 'Change';
	}
}

class StringableObject2
{
	public function __toString()
	{
		return 'Chuck Norris';
	}
}
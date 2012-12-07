<?php
namespace ChangeTests\Change\Stdlib;

class StringTest extends \PHPUnit_Framework_TestCase
{
	public function testToLower()
	{
		$this->assertEquals("abcdefgh0", \Change\Stdlib\String::toLower("AbCdEFgH0"));
		$this->assertEquals("été", \Change\Stdlib\String::toLower("Été"));
		$this->assertEquals("ça", \Change\Stdlib\String::toLower("Ça"));
	}

	public function testToUpper()
	{
		$this->assertEquals("ABCDEFGH0", \Change\Stdlib\String::toUpper("AbCdEFgh0"));
		$this->assertEquals("ÉTÉ", \Change\Stdlib\String::toUpper("été"));
		$this->assertEquals("ÇA", \Change\Stdlib\String::toUpper("ça"));
	}

	public function testUcfirst()
	{
		$this->assertEquals("Été", \Change\Stdlib\String::ucfirst("été"));
		$this->assertEquals("Ça", \Change\Stdlib\String::ucfirst("ça"));
		$this->assertEquals("Čuit", \Change\Stdlib\String::ucfirst("čuit"));
		$this->assertEquals("Alphabet", \Change\Stdlib\String::ucfirst("alphabet"));
	}

	public function testSubstring()
	{
		$this->assertEquals("étç", \Change\Stdlib\String::subString("abcdétçefgh", 4, 3));
	}

	public function testLength()
	{
		$this->assertEquals(3, \Change\Stdlib\String::length("étç"));
	}

	public function testShorten()
	{
		$this->assertEquals("étçétç!", \Change\Stdlib\String::shorten("étçétçétçétçétçétç", 7, "!"));
	}

	public function testRandom()
	{
		$trial1 = \Change\Stdlib\String::random(255);
		$trial2 = \Change\Stdlib\String::random(255);
		// If this test fails, you might be really lucky or facing a bug ;)
		$this->assertNotEquals($trial2, $trial1);

		$string = \Change\Stdlib\String::random(42);
		$this->assertEquals(42, \Change\Stdlib\String::length($string));

	}

	public function testIsEmpty()
	{
		// Empty strings.
		$this->assertTrue(\Change\Stdlib\String::isEmpty(null));
		$this->assertTrue(\Change\Stdlib\String::isEmpty(''));
		$this->assertTrue(\Change\Stdlib\String::isEmpty(' '));
		$this->assertTrue(\Change\Stdlib\String::isEmpty('		'));
		$this->assertTrue(\Change\Stdlib\String::isEmpty("	\n\n	"));
		
		// Not empty strings.
		$this->assertFalse(\Change\Stdlib\String::isEmpty('test'));
		$this->assertFalse(\Change\Stdlib\String::isEmpty('1'));
			
		// Strings-compatible values.
		$this->assertFalse(\Change\Stdlib\String::isEmpty(0));
		$this->assertFalse(\Change\Stdlib\String::isEmpty(125));
		$this->assertFalse(\Change\Stdlib\String::isEmpty(1.25));
			
		// String-incompatible values.
		$this->assertTrue(\Change\Stdlib\String::isEmpty(array()));
		$this->assertTrue(\Change\Stdlib\String::isEmpty(array('test')));
	}
}
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
	
	public function testBeginsWith()
	{
		$this->assertTrue(\Change\Stdlib\String::beginsWith('testing string', 'test'));
		$this->assertFalse(\Change\Stdlib\String::beginsWith('testing string', 'rest'));
		$this->assertTrue(\Change\Stdlib\String::beginsWith('testing string', 'testing string'));
		$this->assertFalse(\Change\Stdlib\String::beginsWith('testing string', 'testing string 123'));
		
		// Should work with UTF-8 characters.
		$this->assertTrue(\Change\Stdlib\String::beginsWith('chaîne de test', 'chaîn'));
		$this->assertFalse(\Change\Stdlib\String::beginsWith('chaîne de test', 'chain'));
		$this->assertFalse(\Change\Stdlib\String::beginsWith('chaîne de test', 'thaîn'));
		$this->assertTrue(\Change\Stdlib\String::beginsWith('chaîne de test', 'chaîne de test'));
		$this->assertFalse(\Change\Stdlib\String::beginsWith('chaîne de test', 'chaîne de test 123'));
	}
	
	public function testEndsWith()
	{
		$this->assertTrue(\Change\Stdlib\String::endsWith('testing string', 'ring'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('testing string', 'rind'));
		$this->assertTrue(\Change\Stdlib\String::endsWith('testing string', 'testing string'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('testing string', 'testing string 123'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('testing string', '123 testing string'));
		
		// Should work with UTF-8 characters.
		$this->assertTrue(\Change\Stdlib\String::endsWith('on a testé', 'esté'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('on a testé', 'este'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('on a testé', 'est'));
		$this->assertTrue(\Change\Stdlib\String::endsWith('on a testé', 'on a testé'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('on a testé', 'on a testé 123'));
		$this->assertFalse(\Change\Stdlib\String::endsWith('on a testé', '123 on a testé'));
	}

	public function testGetSubstitutedString()
	{
		// The order of substitutions does not matter.
		$stringToSubstitute = 'When {inventor} invented the telephone he had 3 missed calls from {missedCallsFrom}.';
		$substitutions = ['missedCallsFrom' => 'Chuck Norris', 'inventor' => 'Alexander Bell'];
		$substitutedString = \Change\Stdlib\String::getSubstitutedString($stringToSubstitute, $substitutions);
		$this->assertEquals('When Alexander Bell invented the telephone he had 3 missed calls from Chuck Norris.', $substitutedString);

		// Substitutions may contain dots and upper cased letters but must start with a lower case letter.
		$stringToSubstitute = 'Fear of {first.fear.object} is arachnophobia, fear of {secondFearObject} is claustrophobia, fear of {Chuck.Norris} is just called Logic.';
		$substitutions = ['first.fear.object' => 'spiders', 'secondFearObject' => 'tight spaces', 'Chuck.Norris' => 'Chuck Norris'];
		$substitutedString = \Change\Stdlib\String::getSubstitutedString($stringToSubstitute, $substitutions);
		$this->assertEquals('Fear of spiders is arachnophobia, fear of tight spaces is claustrophobia, fear of {Chuck.Norris} is just called Logic.', $substitutedString);

		// Substitutions are case sensitive and unknown substitutions are replaced by empty string.
		$stringToSubstitute = '{chuck.Norris} has already been to {where}; that\'s why there are no signs of {life}.';
		$substitutions = ['wHeRe' => 'Mars', 'chuck.Norris' => 'Chuck Norris'];
		$substitutedString = \Change\Stdlib\String::getSubstitutedString($stringToSubstitute, $substitutions);
		$this->assertEquals('Chuck Norris has already been to ; that\'s why there are no signs of .', $substitutedString);

		// Replacement of multiple occurrences.
		$stringToSubstitute = '{who} destroyed {what}, because {who} only recognizes the element of surprise.';
		$substitutions = ['who' => 'Chuck Norris', 'what' => 'the periodic table'];
		$substitutedString = \Change\Stdlib\String::getSubstitutedString($stringToSubstitute, $substitutions);
		$this->assertEquals('Chuck Norris destroyed the periodic table, because Chuck Norris only recognizes the element of surprise.', $substitutedString);

		// Specific regexp...
		$stringToSubstitute = '%who% once had a near-%Chuck% experience.';
		$substitutions = ['Chuck' => 'Chuck Norris', 'who' => 'Death'];
		$substitutedString = \Change\Stdlib\String::getSubstitutedString($stringToSubstitute, $substitutions, '/%([A-Za-z][A-Za-z0-9.]*)%/');
		$this->assertEquals('Death once had a near-Chuck Norris experience.', $substitutedString);
	}
}
<?php
namespace ChangeTests\Change\Injection\Extractor;

class ExtractedClassTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$class = new \Change\Injection\Extractor\ExtractedClass();
		$class->setName("TestClass");
		$class->setAbstract(true);
		$class->setBody('{}');
		$this->assertEquals('abstract class TestClass' . PHP_EOL . '{}', $class->__toString());
	}
	
	public function testInvalidBody()
	{
		$class = new \Change\Injection\Extractor\ExtractedClass();
		$class->setBody(' {}    ');
		// The above is valid
		$this->assertTrue(true);
		$this->setExpectedException('\\InvalidArgumentException');
		$class->setBody(' a{}    ');
	}
	
	public function testRuntimeToString()
	{
		$class = new \Change\Injection\Extractor\ExtractedClass();
		$class->setAbstract(true);
		$class->setBody('{}');
		$this->setExpectedException('\\RuntimeException');
		$class->__toString();
		$class->setName("TestClass");
		$this->assertTrue(true);
	}
}
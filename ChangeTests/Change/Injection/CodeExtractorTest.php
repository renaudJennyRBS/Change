<?php

namespace Tests\Change\Injection;

class CodeExtractorTest extends \PHPUnit_Framework_TestCase
{	
	protected $extractor;
	
	public function testBadConstruct()
	{
		$url = realpath(__DIR__) . '/TestAssets/thisfiledoesnotexist.php';
		$this->setExpectedException('RuntimeException');
		$extractor = new \Change\Injection\CodeExtractor($url);
	}
	
	public function testConstruct()
	{
		$url = realpath(__DIR__) . '/TestAssets/ComplexPhpFile.php';
		return new \Change\Injection\CodeExtractor($url);
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testNamespaces(\Change\Injection\CodeExtractor $extractor)
	{
		$namespaces = $extractor->getNamespaces();
		$this->assertCount(4, $namespaces);
		$this->assertTrue($extractor->hasNamespace('Alpha'));
		$this->assertTrue($extractor->hasNamespace('Beta'));
		$this->assertTrue($extractor->hasNamespace('Gamma'));
		$this->assertTrue($extractor->hasNamespace('Gamma\Zeta'));
		return $extractor;
	}
	
	/**
	 * @depends testNamespaces
	 */
	public function testInterfaces(\Change\Injection\CodeExtractor $extractor)
	{
		$namespaceAlpha = $extractor->getNamespace('Alpha');
		$interfaces = $namespaceAlpha->getDeclaredInterfaces();
		$this->assertCount(2, $interfaces);
		
		$interfaceA = $namespaceAlpha->getDeclaredInterface('InterfaceA');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedInterface', $interfaceA);
		$this->assertNull($interfaceA->getExtendedInterfaceName());
		$this->assertEquals($interfaceA->getBody(), "{
	public function A();
}");
		
		$extendedInterfaceA = $namespaceAlpha->getDeclaredInterface('ExtendingInterfaceA');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedInterface', $extendedInterfaceA);
		$this->assertEquals($extendedInterfaceA->getExtendedInterfaceName(), 'InterfaceA');		
		$this->assertEquals($extendedInterfaceA->getBody(), "{
	public function AA();
}");
		
		$namespaceBeta = $extractor->getNamespace('Beta');
		$interfaces = $namespaceBeta->getDeclaredInterfaces();
		$this->assertCount(1, $interfaces);
		$interfaceB = $namespaceBeta->getDeclaredInterface('InterfaceB');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedInterface', $interfaceB);
		$this->assertNull($interfaceB->getExtendedInterfaceName());
		$this->assertEquals($interfaceB->getBody(), "{
	public function B();
}");
		
		$namespaceGamma = $extractor->getNamespace('Gamma');
		$interfaces = $namespaceGamma->getDeclaredInterfaces();
		$this->assertCount(0, $interfaces);
		
		$namespaceGammaZeta = $extractor->getNamespace('Gamma\Zeta');
		$interfaces = $namespaceGammaZeta->getDeclaredInterfaces();
		$this->assertCount(0, $interfaces);
		return $extractor;
	}
	
	/**
	 * @depends testInterfaces
	 */
	public function testClasses(\Change\Injection\CodeExtractor $extractor)
	{
		$namespaceAlpha = $extractor->getNamespace('Alpha');
		$classes = $namespaceAlpha->getDeclaredClasses();
		$this->assertCount(0, $classes);
		
		$namespaceBeta = $extractor->getNamespace('Beta');
		$classes = $namespaceBeta->getDeclaredClasses();
		$this->assertCount(1, $classes);
		$classC = $namespaceBeta->getDeclaredClass('C');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedClass', $classC);
		$this->assertFalse($classC->getAbstract());
		$this->assertNull($classC->getExtendedClassName());
		$this->assertEmpty($classC->getImplementedInterfaceNames());
		$this->assertEquals($classC->getBody(), '{
	public function C()
	{
		return "I am C";
	}	
}');
		
		$namespaceGamma = $extractor->getNamespace('Gamma');
		$classes = $namespaceGamma->getDeclaredClasses();
		$this->assertCount(2, $classes);
		
		$classTestable = $namespaceGamma->getDeclaredClass('Testable');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedClass', $classTestable);
		$this->assertTrue($classTestable->getAbstract());
		$this->assertNull($classTestable->getExtendedClassName());
		$this->assertCount(2, $classTestable->getImplementedInterfaceNames());
		$this->assertContains('InterfaceA', $classTestable->getImplementedInterfaceNames());
		$this->assertContains('\Beta\InterfaceB', $classTestable->getImplementedInterfaceNames());
		$this->assertEquals($classTestable->getBody(), '{
	const TEST = "A Testable";
	
	public function A()
	{ 
		return "I implement Alpha InterfaceA";	
	}
	
	public static function B()
	{
		return "I implement Beta InterfaceB";	
	}
}');
		$classC = $namespaceGamma->getDeclaredClass('C');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedClass', $classC);
		$this->assertFalse($classC->getAbstract());
		$this->assertEquals('MyC', $classC->getExtendedClassName());
		$this->assertEmpty($classC->getImplementedInterfaceNames());
		$this->assertEquals($classC->getBody(),'{
	
}');
		
		
		$namespaceGammaZeta = $extractor->getNamespace('Gamma\Zeta');
		$classes = $namespaceGammaZeta->getDeclaredClasses();
		$this->assertCount(1, $classes);
		$classTested = $namespaceGammaZeta->getDeclaredClass('Tested');
		$this->assertInstanceOf('\Change\Injection\Extractor\ExtractedClass', $classTested);
		$this->assertFalse($classTested->getAbstract());
		$this->assertEquals('\Gamma\Testable', $classTested->getExtendedClassName());
		$this->assertEmpty($classTested->getImplementedInterfaceNames());
		$this->assertEquals($classTested->getBody(),'{
	public function test()
	{
		return static::TEST;
	}
}');
		return $extractor;
	}
	
	/**
	 * @depends testClasses
	 */
	public function testUses(\Change\Injection\CodeExtractor $extractor)
	{
		$namespaceAlpha = $extractor->getNamespace('Alpha');
		$this->assertEmpty($namespaceAlpha->getDeclaredUses());
		
		$namespaceBeta = $extractor->getNamespace('Beta');
		$this->assertEmpty($namespaceBeta->getDeclaredUses());
		
		$namespaceGamma = $extractor->getNamespace('Gamma');		
		$this->assertCount(2, $namespaceGamma->getDeclaredUses());
		$this->assertContains('use Alpha\InterfaceA;', $namespaceGamma->getDeclaredUses());
		$this->assertContains('use Beta\C as MyC;', $namespaceGamma->getDeclaredUses());
		
		$namespaceGammaZeta = $extractor->getNamespace('Gamma\Zeta');
		$this->assertCount(1, $namespaceGammaZeta->getDeclaredUses());
		$this->assertContains('use \Alpha\InterfaceA as Toto;', $namespaceGammaZeta->getDeclaredUses());
		
	}
	
	public function testExtractClassWithoutNamespace()
	{
		$url = realpath(__DIR__) . '/TestAssets/ClassWithNoNamespace.php';
		$extractor = new \Change\Injection\CodeExtractor($url);
		$namespace = $extractor->getNamespace('');
		$this->assertCount(1, $namespace->getDeclaredClasses());
	}
}
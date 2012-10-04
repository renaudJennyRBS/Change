<?php
namespace ChangeTests\Change\Injection;

class ClassInjectionTest extends \PHPUnit_Framework_TestCase
{
	public function testBadArgsConstruct()
	{
		$originalInfoBad1 = array(	'names' => '\ChangeTests\Change\Injection\TestAssets\Alpha\A',
			'path' => __DIR__ . '/TestAssets/Alpha/A.php'
		);
		
		$replacingInfos = 	array(
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Beta\B',
				'path' => __DIR__ . '/TestAssets/Beta/B.php'
			),
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Gamma\C',
				'path' => __DIR__ . '/TestAssets/Gamma/C.php'
			)
		);
		
		$this->setExpectedException('\InvalidArgumentException', 'first argument of __construct must have at least the key "name" set to a fully-qualified class name');
		$injection = new \Change\Injection\ClassInjection($originalInfoBad1, $replacingInfos);		
	}
	
	public function testBadArgsConstruct2()
	{
		$replacingInfos = 	array(
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Beta\B',
				'path' => __DIR__ . '/TestAssets/Beta/B.php'
			),
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Gamma\C',
				'path' => __DIR__ . '/TestAssets/Gamma/C.php'
			)
		);
		
		$originalInfoBad2 = array(	'name' => 'ChangeTests\Change\Injection\TestAssets\Alpha\A',
			'path' => __DIR__ . '/TestAssets/Alpha/A.php'
		);
		
		$this->setExpectedException('\InvalidArgumentException', 'first argument of __construct must have at least the key "name" set to a fully-qualified class name');
		$injection = new \Change\Injection\ClassInjection($originalInfoBad2, $replacingInfos);
	}
	
	public function testBadArgsConstruct3()
	{
		$originalInfo = array(	'name' => '\ChangeTests\Change\Injection\TestAssets\Alpha\A',
			'path' => __DIR__ . '/TestAssets/Alpha/A.php'
		);
	
		$replacingInfosBad1 = 	array(
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Beta\B',
				'path' => __DIR__ . '/TestAssets/Beta/B.php'
			),
			array(
				'path' => __DIR__ . '/TestAssets/Gamma/C.php'
			)
		);
	
		$this->setExpectedException('\InvalidArgumentException', 'all entries of the second argument of __construct must have at least one key "name" set to a fully-qualified class name');
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfosBad1);
	}
	
	public function testBadArgsConstruct4()
	{
		$originalInfo = array(	'name' => '\ChangeTests\Change\Injection\TestAssets\Alpha\A',
			'path' => __DIR__ . '/TestAssets/Alpha/A.php'
		);
	
		$replacingInfosBad2 = 	array(
			array(
				'name' => 'ChangeTests\Change\Injection\TestAssets\Beta\B',
				'path' => __DIR__ . '/TestAssets/Beta/B.php'
			),
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Gamma\C',
				'path' => __DIR__ . '/TestAssets/Gamma/C.php'
			)
		);
	
		$this->setExpectedException('\InvalidArgumentException', 'all entries of the second argument of __construct must have at least one key "name" set to a fully-qualified class name');
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfosBad2);
	}
	
	public function testConstruct()
	{
		$originalInfo = array(	'name' => '\ChangeTests\Change\Injection\TestAssets\Alpha\A',
									'path' => __DIR__ . '/TestAssets/Alpha/A.php'
							);
		$replacingInfos = 	array(
							 	array(
							 		'name' => '\ChangeTests\Change\Injection\TestAssets\Beta\B',
							 		'path' => __DIR__ . '/TestAssets/Beta/B.php'
								),
								array(
									'name' => '\ChangeTests\Change\Injection\TestAssets\Gamma\C',
									'path' => __DIR__ . '/TestAssets/Gamma/C.php'
								)
			);

		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		
		return $injection;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testCompile(\Change\Injection\ClassInjection $injection)
	{
		$compilationResult = $injection->compile();	
		$result = $compilationResult['compiled'];
		$this->assertCount(5, $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\A_injected0', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Beta\B', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\A_injected1', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Gamma\C', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\A', $result);
		foreach ($result as $className => $entry)
		{
			$this->assertArrayHasKey('path', $entry);
			$this->assertArrayHasKey('mtime', $entry);
			$this->assertFileExists($entry['path']);
		}
		return $result;
	}

	/**
	 * @depends testCompile
	 */
	public function testInjection(array $result)
	{		
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\A_injected0']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Beta\B']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\A_injected1']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Gamma\C']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\A']['path'];
		$instance = new \ChangeTests\Change\Injection\TestAssets\Alpha\A();
		$this->assertEquals($instance->test(), 'C');
	}
	
	public function testConstructNoNamespace()
	{
		$originalInfo = array(	'name' => '\TestClass',
			'path' => __DIR__ . '/TestAssets/TestClass.php'
		);
		$replacingInfos = 	array(
			array(
				'name' => '\InjectingClass',
				'path' => __DIR__ . '/TestAssets/InjectingClass.php'
			)
		);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		return $injection;
	}
	
	/**
	 * @depends testConstructNoNamespace
	 */
	public function testCompileNoNamespace(\Change\Injection\ClassInjection $injection)
	{
		$compilationResult = $injection->compile();	
		$result = $compilationResult['compiled'];
		$this->assertCount(3, $result);
		$this->assertArrayHasKey('\TestClass_injected0', $result);
		$this->assertArrayHasKey('\InjectingClass', $result);
		$this->assertArrayHasKey('\TestClass', $result);
		foreach ($result as $className => $entry)
		{
			$this->assertArrayHasKey('path', $entry);
			$this->assertArrayHasKey('mtime', $entry);
			$this->assertFileExists($entry['path']);
		}
		return $result;
	}
	
	/**
	 * @depends testCompileNoNamespace
	 */
	public function testInjectionNoNamespace(array $result)
	{
		require_once $result['\TestClass_injected0']['path'];
		require_once $result['\InjectingClass']['path'];
		require_once $result['\TestClass']['path'];
		$instance = new \TestClass();
		$this->assertEquals($instance->test(), 'Injected!');
	}
	
	
	public function testConstructMixed()
	{
		$originalInfo = array(	'name' => '\TestClass2',
			'path' => __DIR__ . '/TestAssets/TestClass2.php'
		);
		$replacingInfos = 	array(
			array(
				'name' => '\ChangeTests\Change\Injection\TestAssets\Alpha\InjectingTestClass2',
				'path' => __DIR__ . '/TestAssets/Alpha/InjectingTestClass2.php'
			)
		);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		return $injection;
	}
	
	/**
	 * @depends testConstructMixed
	 */
	public function testCompileMixed(\Change\Injection\ClassInjection $injection)
	{
		$compilationResult = $injection->compile();	
		$result = $compilationResult['compiled'];
		$this->assertCount(3, $result);
		$this->assertArrayHasKey('\TestClass2_injected0', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\InjectingTestClass2', $result);
		$this->assertArrayHasKey('\TestClass2', $result);
		foreach ($result as $className => $entry)
		{
			$this->assertArrayHasKey('path', $entry);
			$this->assertArrayHasKey('mtime', $entry);
			$this->assertFileExists($entry['path']);
		}
		return $result;
	}
	
	/**
	 * @depends testCompileMixed
	 */
	public function testInjectionMixed(array $result)
	{
		require_once $result['\TestClass2_injected0']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\InjectingTestClass2']['path'];
		require_once $result['\TestClass2']['path'];
		$instance = new \TestClass2();
		$this->assertEquals($instance->test(), '\ChangeTests\Change\Injection\TestAssets\Alpha\InjectingTestClass2');
	}
	
	public function testConstructMixed2()
	{
		$originalInfo = array(	'name' => '\ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3',
			'path' => __DIR__ . '/TestAssets/Alpha/TestClass3.php'
		);
		$replacingInfos = 	array(
			array(
				'name' => '\InjectingTestClass3',
				'path' => __DIR__ . '/TestAssets/InjectingTestClass3.php'
			)
		);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		return $injection;
	}
	
	/**
	 * @depends testConstructMixed2
	 */
	public function testCompileMixed2(\Change\Injection\ClassInjection $injection)
	{
		$compilationResult = $injection->compile();	
		$result = $compilationResult['compiled'];
		$this->assertCount(3, $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3_injected0', $result);
		$this->assertArrayHasKey('\InjectingTestClass3', $result);
		$this->assertArrayHasKey('\ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3', $result);
		foreach ($result as $className => $entry)
		{
			$this->assertArrayHasKey('path', $entry);
			$this->assertArrayHasKey('mtime', $entry);
			$this->assertFileExists($entry['path']);
		}
		return $result;
	}
	
	/**
	 * @depends testCompileMixed2
	 */
	public function testInjectionMixed2(array $result)
	{
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3_injected0']['path'];
		require_once $result['\InjectingTestClass3']['path'];
		require_once $result['\ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3']['path'];
		$instance = new \ChangeTests\Change\Injection\TestAssets\Alpha\TestClass3();
		$this->assertEquals($instance->test(), '\InjectingTestClass3');
	}
	
}

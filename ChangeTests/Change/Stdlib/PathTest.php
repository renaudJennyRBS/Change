<?php

namespace ChangeTests\Change\Stdlib;

class PathTest extends \PHPUnit_Framework_TestCase
{
	public function testAppPath()
	{
		$path = \Change\Stdlib\Path::appPath('test1', 'test2', 'test3');
		$this->assertEquals(PROJECT_HOME . DIRECTORY_SEPARATOR . 'App' 
				. DIRECTORY_SEPARATOR . 'test1' . DIRECTORY_SEPARATOR . 'test2' . DIRECTORY_SEPARATOR . 'test3' , $path);
	}
	
	public function testCompilationPath()
	{
		$path = \Change\Stdlib\Path::compilationPath('test1', 'test2', 'test3');
		$this->assertEquals(PROJECT_HOME . DIRECTORY_SEPARATOR . 'Compilation'
			. DIRECTORY_SEPARATOR . 'test1' . DIRECTORY_SEPARATOR . 'test2' . DIRECTORY_SEPARATOR . 'test3' , $path);
	}
	
	public function testProjectPath()
	{
		$path = \Change\Stdlib\Path::projectPath('test1', 'test2', 'test3');
		$this->assertEquals(PROJECT_HOME . DIRECTORY_SEPARATOR 
			 . 'test1' . DIRECTORY_SEPARATOR . 'test2' . DIRECTORY_SEPARATOR . 'test3' , $path);
	}

	public function testBuildPathFromComponents()
	{
		$path = \Change\Stdlib\Path::buildPathFromComponents(array('a', 'b', 'c'));
		$this->assertEquals(DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b'. DIRECTORY_SEPARATOR . 'c'  , $path);
	}
}
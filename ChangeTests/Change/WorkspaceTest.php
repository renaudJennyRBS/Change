<?php

namespace ChangeTests\Change;

use Change\Workspace;

/**
 */
class WorkspaceTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$workspace = new Workspace();
		return $workspace;
	}

	/**
	 * @depends testConstruct
	 */
	public function testAppPath(Workspace $workspace)
	{
		$expected = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'App','Dir1', 'Dir2', 'Dir3', 'File.php'));
		$this->assertEquals($expected, $workspace->appPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testCompilationPath(Workspace $workspace)
	{
		$expected = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'Compilation','Dir1', 'Dir2', 'Dir3', 'File.php'));
		$this->assertEquals($expected, $workspace->compilationPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testProjectPath(Workspace $workspace)
	{
		$expected = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'Dir1', 'Dir2', 'Dir3', 'File.php'));
		$this->assertEquals($expected, $workspace->projectPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testProjectModulesPath(Workspace $workspace)
	{
		$expected = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'App','Modules', 'Dir1', 'Dir2', 'Dir3', 'File.php'));
		$this->assertEquals($expected, $workspace->projectModulesPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testPluginsModulesPath(Workspace $workspace)
	{
		$expected = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'Plugins','Modules','Dir1', 'Dir2', 'Dir3', 'File.php'));
		$this->assertEquals($expected, $workspace->pluginsModulesPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}
}
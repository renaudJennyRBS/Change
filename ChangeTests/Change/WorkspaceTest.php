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
		$this->assertEquals(PROJECT_HOME . '/App/Dir1/Dir2/Dir3/File.php', $workspace->appPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testCompilationPath(Workspace $workspace)
	{
		$this->assertEquals(PROJECT_HOME . '/Compilation/Dir1/Dir2/Dir3/File.php', $workspace->compilationPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testProjectPath(Workspace $workspace)
	{
		$this->assertEquals(PROJECT_HOME . '/Dir1/Dir2/Dir3/File.php', $workspace->projectPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testProjectModulesPath(Workspace $workspace)
	{
		$this->assertEquals(PROJECT_HOME . '/App/Modules/Dir1/Dir2/Dir3/File.php', $workspace->projectModulesPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testPluginsModulesPath(Workspace $workspace)
	{
		$this->assertEquals(PROJECT_HOME . '/Plugins/Modules/Dir1/Dir2/Dir3/File.php', $workspace->pluginsModulesPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testLibrariesPath(Workspace $workspace)
	{
		$this->assertEquals(PROJECT_HOME . '/Libraries/Dir1/Dir2/Dir3/File.php', $workspace->librariesPath('Dir1', 'Dir2', 'Dir3', 'File.php'));
	}
}
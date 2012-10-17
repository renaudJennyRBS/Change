<?php

namespace ChangeTests\Change\TestAssets;

class UnitTestWorkspace extends \Change\Workspace
{
	public function clear()
	{
		\Change\Stdlib\File::rmdir(PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/Compilation', true);
	}
	
	/**
	 *
	 * @return string
	 */
	protected function appBase()
	{
		return PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/App';
	}
	
	/**
	 *
	 * @return string
	 */
	protected function compilationBase()
	{
		// TODO Auto-generated method stub
		return PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/Compilation';
	}
	
	/**
	 *
	 * @return string
	 */
	protected function projectBase()
	{
		// TODO Auto-generated method stub
		return PROJECT_HOME . '/ChangeTests/UnitTestWorkspace';
	}
}
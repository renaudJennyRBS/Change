<?php

namespace ChangeTests\Change\Configuration\TestAssets;

class Application extends \Change\Application
{
	public function __construct()
	{
		
	}
	
	/**
	 * @return string
	 */
	public function getCompiledConfigurationPath()
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'project.php';
	}
}
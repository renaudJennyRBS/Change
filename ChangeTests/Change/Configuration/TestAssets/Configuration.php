<?php
namespace ChangeTests\Change\Configuration\TestAssets;

/**
 * Make some protected methods public for test.
 */
class Configuration extends \Change\Configuration\Configuration
{
	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application, $compiledConfigPath = null)
	{
		$this->compiledConfiguration = $compiledConfigPath;
		// TODO Auto-generated method stub
		parent::__construct($application);
	}

	/**
	 * Setup constants.
	 */
	public function applyDefines()
	{
		parent::applyDefines();
	}
	
	protected $compiledConfiguration;
	
	public function getCompiledConfigPath()
	{
		if ($this->compiledConfiguration)
		{
			return $this->compiledConfiguration;
		}
		return parent::getCompiledConfigPath();
	}	
}
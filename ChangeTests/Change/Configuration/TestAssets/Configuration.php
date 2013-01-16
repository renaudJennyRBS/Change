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
	public function __construct(\Change\Application $application, $compiledConfigPath = null, $compiledDefinesPath = null)
	{
		$this->compiledConfigurationPath = $compiledConfigPath;
		$this->compiledDefinesPath = $compiledDefinesPath;
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

	protected $compiledConfigurationPath;
	
	protected $compiledDefinesPath;

	public function getCompiledConfigPath()
	{
		if ($this->compiledConfigurationPath)
		{
			return $this->compiledConfigurationPath;
		}
		return parent::getCompiledConfigPath();
	}
	
	public function getCompiledDefinesPath()
	{
		if ($this->compiledDefinesPath)
		{
			return $this->compiledDefinesPath;
		}
		return parent::getCompiledDefinesPath();
	}

	public function clear()
	{
		return parent::clear();
	}


	public function isCompiled()
	{
		return parent::isCompiled();
	}

}
<?php

namespace ChangeTests\Change\Configuration\TestAssets;

/**
 * Make some protected methods public for test.
 */
class Configuration extends \Change\Configuration\Configuration
{
	/**
	 * Setup constants.
	 */
	public function applyDefines()
	{
		parent::applyDefines();
	}
}
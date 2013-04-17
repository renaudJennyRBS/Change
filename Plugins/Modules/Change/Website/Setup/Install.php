<?php
namespace Change\Website\Setup;

/**
 * Class Install
 * @package Change\Website\Setup
 * @name \Change\Website\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Application $application
	 */
	public function execute($application)
	{
		$application->getConfiguration()->addPersistentEntry('Change/Presentation/Blocks/Change_Website', '\\Change\\Website\\Blocks\\SharedListenerAggregate');
	}
}
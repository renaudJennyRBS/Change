<?php
namespace Rbs\Storeshipping\Setup;

/**
 * @name \Rbs\Storeshipping\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	public function executeDbSchema($plugin, $schemaManager)
	{
		parent::executeDbSchema($plugin, $schemaManager);
		$schema = new Schema($schemaManager);
		$schema->generate();
	}
}

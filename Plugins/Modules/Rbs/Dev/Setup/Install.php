<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Setup;

/**
 * @name \Rbs\Dev\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		// To activate events logging, add this entry in your project.json.
		//$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Dev',
		//	'\Rbs\Dev\Events\SharedListeners');

		$configuration->addPersistentEntry('Change/Events/Commands/Rbs_Dev', '\Rbs\Dev\Events\Commands\Listeners');
	}

}

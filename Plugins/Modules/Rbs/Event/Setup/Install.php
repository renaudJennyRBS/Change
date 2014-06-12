<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Setup;

/**
 * @name \Rbs\Event\Setup\Install
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
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Event', '\Rbs\Event\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Event', '\Rbs\Event\Events\CollectionManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/PageManager/Rbs_Event', '\Rbs\Event\Events\PageManager\Listeners');
	}
}

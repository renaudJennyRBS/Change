<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Setup;

/**
 * @name \Rbs\Order\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	public function executeDbSchema($plugin, $schemaManager)
	{
		$schema = new Schema($schemaManager);
		$schema->generate();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}

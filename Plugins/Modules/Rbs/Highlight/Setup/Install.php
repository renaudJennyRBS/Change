<?php
/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Highlight\Setup;

/**
 * @name \Rbs\Highlight\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function attach($events, $plugin)
//	{
//		parent::attach($events, $plugin);
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function initialize($plugin)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Highlight', '\Rbs\Highlight\Blocks\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
//	public function executeServices($plugin, $applicationServices)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function finalize($plugin)
//	{
//	}
}

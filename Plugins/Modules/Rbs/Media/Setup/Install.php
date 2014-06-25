<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Setup;

/**
 * @name \Rbs\Media\Setup\Install
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
		$webBaseDirectory = $configuration->getEntry('Change/Install/webBaseDirectory', '');
		if (!empty($webBaseDirectory))
		{
			$formattedPath = $application->getWorkspace()->composePath($webBaseDirectory, 'Imagestorage', 'images');
		}
		else
		{
			$formattedPath = $application->getWorkspace()->composePath('Imagestorage', 'images');
		}
		$images = $configuration->getEntry('Change/Storage/images', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalImageStorage',
			'basePath' => 'App/Storage/images',
			'formattedPath' => $formattedPath,
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/images', $images);

		$videos = $configuration->getEntry('Change/Storage/videos', array());
		$videos = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/videos',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $videos);
		$configuration->addPersistentEntry('Change/Storage/videos', $videos);

		$files = $configuration->getEntry('Change/Storage/files', array());
		$files = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/files',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $files);
		$configuration->addPersistentEntry('Change/Storage/files', $files);
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

}
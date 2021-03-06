<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Setup;

/**
 * @name \Rbs\Website\Setup\Install
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
		$webBaseDirectory = $application->getWorkspace()->composeAbsolutePath($configuration->getEntry('Change/Install/webBaseDirectory'));
		if (is_dir($webBaseDirectory))
		{
			$srcPath = __DIR__ . '/Assets/index.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($application->getWorkspace()->projectPath(), true), $content);
			\Change\Stdlib\File::write($webBaseDirectory . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: '. $webBaseDirectory .
			'. Check "Change/Install/webBaseDirectory" configuration entry.', 999999);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
		$rootNode = $applicationServices->getTreeManager()->getRootNode('Rbs_Website');
		if (!$rootNode)
		{
			$transactionManager = $applicationServices->getTransactionManager();

			try
			{
				$transactionManager->begin();

				/* @var $folder \Rbs\Generic\Documents\Folder */
				$folder = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
				$folder->setLabel('Rbs_Website');
				$folder->create();
				$applicationServices->getTreeManager()->insertRootNode($folder, 'Rbs_Website');

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
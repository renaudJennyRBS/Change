<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Setup;

/**
 * @name \Rbs\Commerce\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Commerce', '\Rbs\Commerce\Events\SharedListeners');
		$configuration->addPersistentEntry('Change/Events/AuthenticationManager/Rbs_Commerce', '\Rbs\Commerce\Events\AuthenticationManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Commerce', '\Rbs\Commerce\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Commerce', '\Rbs\Commerce\Events\CollectionManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/Commands/Rbs_Commerce', '\Rbs\Commerce\Events\Commands\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Admin/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Admin\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Rest/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Rest\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Web/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Web\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Ajax/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Ajax\Listeners');
		$configuration->addPersistentEntry('Change/Events/JobManager/Rbs_Commerce', '\Rbs\Commerce\Events\JobManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/ModelManager/Rbs_Commerce', '\Rbs\Commerce\Events\ModelManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/PageManager/Rbs_Commerce', '\Rbs\Commerce\Events\PageManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/ProfileManager/Rbs_Commerce', '\Rbs\Commerce\Events\ProfileManager\Listeners');

		$configuration->addPersistentEntry('Rbs/Commerce/Events/CartManager/Rbs_Commerce', '\Rbs\Commerce\Cart\Listeners');
		$configuration->addPersistentEntry('Rbs/Commerce/Events/ProcessManager/Rbs_Commerce', '\Rbs\Commerce\Events\ProcessManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Commerce/Events/PriceManager/Rbs_Commerce', '\Rbs\Commerce\Events\PriceManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Geo/Events/GeoManager/Rbs_Commerce', '\Rbs\Commerce\Events\GeoManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Mail/Events/MailManager/Rbs_Commerce', '\Rbs\Commerce\Events\MailManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Payment/Events/PaymentManager/Rbs_Commerce', '\Rbs\Commerce\Events\PaymentManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Seo/Events/SeoManager/Rbs_Commerce', '\Rbs\Commerce\Events\SeoManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Admin/Events/AdminManager/Rbs_Commerce', '\Rbs\Commerce\Events\AdminManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Productreturn/Events/ReturnManager/Rbs_Commerce', '\Rbs\Commerce\Events\ReturnManager\Listeners');

		$configuration->addPersistentEntry('Rbs/Media/namedImageFormats/cartItem', '160x120');

		$configuration->addPersistentEntry('Rbs/Commerce/Cart/CleanupTTL', 60 * 60);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{
		$schema = new Schema($schemaManager);
		$schema->generate();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$jobManager = $applicationServices->getJobManager();
		$name = 'Rbs_Commerce_Carts_Cleanup';
		$ids = $jobManager->getJobIdsByName($name);

		if (count($ids) === 0)
		{
			$jobManager->createNewJob($name, null, null, true);
		}
		else
		{
			$first = true;
			foreach ($ids as $id)
			{
				$job = $jobManager->getJob($id);
				if (!$job)
				{
					continue;
				}

				if ($first && $job->getStatus() !== \Change\Job\JobInterface::STATUS_WAITING)
				{
					$jobManager->updateJobStatus($job, \Change\Job\JobInterface::STATUS_WAITING,
						['reportedAt' => new \DateTime()]);
				}
				else if (!$first && $job->getStatus() !== \Change\Job\JobInterface::STATUS_FAILED)
				{
					$jobManager->updateJobStatus($job, \Change\Job\JobInterface::STATUS_FAILED);
				}

				$first = false;
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

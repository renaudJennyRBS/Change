<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Setup;

/**
 * @name \Rbs\User\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
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
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$groupModel = $applicationServices->getModelManager()->getModelByName('Rbs_User_Group');

			$query = $applicationServices->getDocumentManager()->getNewQuery($groupModel);
			if ($query->andPredicates($query->eq('realm', 'Rbs_Admin'))->getCountDocuments() === 0)
			{
				/* @var $group \Rbs\User\Documents\Group */
				$group = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
				$group->setLabel('Backoffice');
				$group->setRealm('Rbs_Admin');
				$group->setIdentifier('backoffice');
				$group->create();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery($groupModel);
			if ($query->andPredicates($query->eq('realm', 'web'))->getCountDocuments() === 0)
			{
				/* @var $group2 \Rbs\User\Documents\Group */
				$group2 = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
				$group2->setLabel('Site Web');
				$group2->setRealm('web');
				$group2->setIdentifier('web');
				$group2->create();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		$jobManager = $applicationServices->getJobManager();
		if (count($jobManager->getJobIdsByName('Rbs_User_CleanAccountRequestTable')) == 0)
		{
			$jobManager->createNewJob('Rbs_User_CleanAccountRequestTable');
		}

		// Init collection
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_User_Collection_Title') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');

				$item = $collection->newCollectionItem();
				$item->setValue('m.');
				$item->setLabel('m.');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.user.setup.mister', array('ucf')));
				$item->setLocked(true);

				$item2 = $collection->newCollectionItem();
				$item2->setValue('mme');
				$item2->setLabel('mme');
				$item2->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.user.setup.miss', array('ucf')));
				$item2->setLocked(true);


				$collection->setLabel('User title');
				$collection->setCode('Rbs_User_Collection_Title');
				$collection->setLocked(true);
				$collection->getItems()->add($item);
				$collection->getItems()->add($item2);
				$collection->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
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
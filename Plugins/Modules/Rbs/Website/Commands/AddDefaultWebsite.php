<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Website\Commands\AddDefaultWebsite
 */
class AddDefaultWebsite
{
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		/* @var $website \Rbs\Website\Documents\Website */
		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Website_Website');
		if ($query->getCountDocuments())
		{
			$response->addCommentMessage('Default Website already exist.');
			return;
		}
		$transactionManager = $applicationServices->getTransactionManager();

		try
		{
			$transactionManager->begin();

			$website = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');

			$website->setLabel('Default Website');
			$website->getCurrentLocalization()->setTitle('Default Website');
			$website->setBaseurl($event->getParam('baseURL'));
			$website->create();
			$wsn = $applicationServices->getTreeManager()->getNodeByDocument($website);
			if ($wsn === null)
			{
				$rootNode = $applicationServices->getTreeManager()->getRootNode('Rbs_Website');
				$applicationServices->getTreeManager()->insertNode($rootNode, $website);
			}

			$response->addInfoMessage('Default website successfully added at: ' . $website->getBaseurl());
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$transactionManager->rollBack($e);
			$response->addErrorMessage($e->getMessage());
		}
	}
}
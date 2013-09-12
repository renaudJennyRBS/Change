<?php
namespace Rbs\Website\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Website\Commands\AddDefaultWebsite
 */
class AddDefaultWebsite
{
	public function execute(Event $event)
	{
		$applicationServices = new \Change\Application\ApplicationServices($event->getApplication());
		$documentServices = new \Change\Documents\DocumentServices($applicationServices);

		/* @var $website \Rbs\Website\Documents\Website */
		$query = new  \Change\Documents\Query\Query($documentServices, 'Rbs_Website_Website');
		if ($query->getCountDocuments())
		{
			$event->addCommentMessage('Default Website already exist.');
			return;
		}
		$transactionManager = $applicationServices->getTransactionManager();

		try
		{
			$transactionManager->begin();

			$website = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');

			$website->setLabel('Default Website');
			$website->getCurrentLocalization()->setTitle('Default Website');
			$website->setBaseurl($event->getParam('baseURL'));
			$website->create();
			$wsn = $documentServices->getTreeManager()->getNodeByDocument($website);
			if ($wsn === null)
			{
				$rootNode = $documentServices->getTreeManager()->getRootNode('Rbs_Website');
				$documentServices->getTreeManager()->insertNode($rootNode, $website);
			}

			$event->addInfoMessage('Default website successfully added at: ' . $website->getBaseurl());
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$transactionManager->rollBack($e);
			$event->addErrorMessage($e->getMessage());
		}
	}
}
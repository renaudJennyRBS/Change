<?php
namespace Rbs\Elasticsearch\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Elasticsearch\Commands\Index
 */
class Index
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$applicationServices = $event->getApplicationServices();
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			$response->addErrorMessage('Generic services not registered');
			return;
		}
		$indexManager = $genericServices->getIndexManager();

		$hasClient = false;
		$all = $event->getParam('all') == true;
		$publishable = $event->getParam('publishable') == true;
		$specificModelName = $event->getParam('model');
		if (!is_string($specificModelName) || count(explode('_', $specificModelName)) != 3)
		{
			$specificModelName = null;
		}

		if (!$all && !$publishable && !$specificModelName)
		{
			$response->addCommentMessage('No model specified.');
			return;
		}
		foreach ($indexManager->getClientsName() as $clientName)
		{
			try
			{
				$client = $indexManager->getClient($clientName);
				if ($client)
				{
					$srvStat = $client->getStatus()->getServerStatus();
					if (isset($srvStat['ok']) && $srvStat['ok'])
					{
						$hasClient = true;
						break;
					}
				}
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->exception($e);
			}
		}

		if ($hasClient)
		{
			if ($event->getParam('useJob'))
			{
				$jobManager = $applicationServices->getJobManager();
			}
			else
			{
				$jobManager = null;
			}

			$documentCount = 0;
			foreach ($applicationServices->getModelManager()->getModelsNames() as $modelName)
			{
				$model = $applicationServices->getModelManager()->getModelByName($modelName);
				if ($model->isAbstract() || $model->isStateless())
				{
					continue;
				}

				if (!$all)
				{
					if ($publishable && !$model->isPublishable())
					{
						continue;
					}
					if ($specificModelName && $modelName != $specificModelName)
					{
						continue;
					}
				}
				if ($jobManager)
				{
					$response->addInfoMessage('Schedule indexation of ' . $modelName . ' model...');
				}
				else
				{
					$response->addInfoMessage('Indexing ' . $modelName . ' model...');
				}

				$LCID = $applicationServices->getDocumentManager()->getLCID();
				$id = 0;
				while (true)
				{
					$toIndex = array();
					$applicationServices->getDocumentManager()->reset();
					$q = $applicationServices->getDocumentManager()->getNewQuery($model);
					$q->andPredicates($q->gt('id', $id));
					$q->addOrder('id');
					$docs = $q->getDocuments(0, 50);

					foreach ($docs as $doc)
					{
						$documentCount++;
						if ($doc instanceof \Change\Documents\Interfaces\Localizable)
						{
							foreach ($doc->getLCIDArray() as $LCID)
							{
								$toIndex[] = array('id' => $doc->getId(), 'model' => $model->getName(), 'LCID' => $LCID,
									'deleted' => false);
							}
						}
						elseif ($doc instanceof \Change\Documents\AbstractDocument)
						{
							$toIndex[] = array('id' => $doc->getId(), 'model' => $model->getName(), 'LCID' => $LCID,
								'deleted' => false);
						}
					}

					if (count($toIndex))
					{
						if ($jobManager)
						{
							$jobManager->createNewJob('Elasticsearch_Index', $toIndex);
						}
						else
						{
							$indexManager->dispatchIndexationEvents($toIndex);
						}
					}

					if ($docs->count() < 50)
					{
						break;
					}
					else
					{
						$id = max($docs->ids());
					}
				}
			}

			if ($jobManager)
			{
				$response->addInfoMessage('Indexation of ' . $documentCount . ' documents are scheduled.');
			}
			else
			{
				$response->addInfoMessage($documentCount . ' documents are indexed.');
			}
		}
		else
		{
			$response->addErrorMessage('No active client detected.');
		}
	}
}
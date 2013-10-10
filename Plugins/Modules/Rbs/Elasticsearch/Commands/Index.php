<?php
namespace Rbs\Elasticsearch\Commands;

use Change\Commands\Events\Event;
use Rbs\Elasticsearch\Services\IndexManager;

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
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);
		$im = new IndexManager();
		$im->setApplicationServices($applicationServices);
		$hasClient = false;

		foreach($im->getClientsName() as $clientName)
		{
			try
			{
				$client = $im->getClient($clientName);
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
			$documentServices  = new \Change\Documents\DocumentServices($applicationServices);
			$jm = new \Change\Job\JobManager();
			$jm->setApplicationServices($applicationServices);

			$documentCount = 0;
			foreach ($documentServices->getModelManager()->getModelsNames() as $modelsName)
			{
				$model = $documentServices->getModelManager()->getModelByName($modelsName);
				$LCID = $documentServices->getDocumentManager()->getLCID();
				$id = 0;
				while (true)
				{
					$toIndex = array();

					$documentServices->getDocumentManager()->reset();
					$q = new \Change\Documents\Query\Query($documentServices, $model);
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
								$toIndex[] = array('id' => $doc->getId(), 'model' => $model->getName(), 'LCID' => $LCID, 'deleted' => false);
							}
						}
						elseif ($doc instanceof \Change\Documents\AbstractDocument)
						{
							$toIndex[] = array('id' => $doc->getId(), 'model' => $model->getName(), 'LCID' => $LCID, 'deleted' => false);
						}
					}

					if (count($toIndex))
					{
						$jm->createNewJob('Elasticsearch_Index', $toIndex);
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
			$event->addErrorMessage('Indexation of ' .$documentCount . ' documents are scheduled.');
		}
		else
		{
			$event->addErrorMessage('No active client detected.');
		}
	}
}
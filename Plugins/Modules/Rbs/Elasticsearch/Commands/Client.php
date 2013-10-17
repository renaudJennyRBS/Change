<?php
namespace Rbs\Elasticsearch\Commands;

use Change\Commands\Events\Event;
use Rbs\Elasticsearch\Services\IndexManager;

/**
 * @name \Rbs\Elasticsearch\Commands\Client
 */
class Client
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

		if (is_string($name = $event->getParam('name')))
		{
			$client = $im->getClient($name);
			if ($client)
			{
				try
				{
					$status = $client->getStatus();
					$srvStat = $status->getServerStatus();
					if ($srvStat['ok'])
					{
						$event->addInfoMessage('Server: ' . $srvStat['name'] . ' (' . $srvStat['version']['number'] . ') is ok ('
						. $srvStat['status'] . ')');
						if ($event->getParam('list'))
						{
							$im->setDocumentServices(new \Change\Documents\DocumentServices($applicationServices));
							foreach ($status->getIndexNames() as $indexName)
							{
								$indexDef = $im->findIndexDefinitionByName($name, $indexName);
								if ($indexDef)
								{
									$event->addInfoMessage('Declared index: ' . $name . '/'. $indexName . ', mapping: ' . $indexDef->getMappingName()
									.', language: ' . $indexDef->getAnalysisLCID());
								}
								else
								{
									$event->addInfoMessage('Ignored index: ' . $indexName);
								}
							}
						}
					}
					else
					{
						$event->addErrorMessage('Error: ' . print_r($srvStat, true));
					}
				}
				catch (\Exception $e)
				{
					$applicationServices->getLogging()->exception($e);
					$event->addErrorMessage('Error on client ' . $name . ': ' . $e->getMessage());
				}
			}
			else
			{
				$event->addErrorMessage('Invalid client name: ' . $name);
			}
		}
		else
		{

			$clientsName = $im->getClientsName();
			if (count($clientsName))
			{
				$event->addInfoMessage('Declared clients: ' . implode(', ', $clientsName));
			}
			else
			{
				$event->addCommentMessage('No declared client.');
			}
		}
	}
}
<?php
namespace Rbs\Elasticsearch\Commands;

use Change\Commands\Events\Event;

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
		$documentServices = new \Change\Documents\DocumentServices($applicationServices);

		$elasticsearchServices = new \Rbs\Elasticsearch\ElasticsearchServices($applicationServices, $documentServices);
		$indexManager = $elasticsearchServices->getIndexManager();

		if (is_string($name = $event->getParam('name')))
		{
			$client = $indexManager->getClient($name);
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
							foreach ($indexManager->getIndexesDefinition($name) as $indexDef)
							{
								$event->addInfoMessage('Declared index "' . $indexDef->getClientName() . '/'
									. $indexDef->getName() . '", mapping: ' . $indexDef->getMappingName()
									. ', language: ' . $indexDef->getAnalysisLCID());
								$idx = $client->getIndex($indexDef->getName());
								if ($idx->exists())
								{
									$status = $idx->getStatus();
									$numDocs = $status->get('docs')['num_docs'];
									$size = $status->get('index')['size'];
									$event->addInfoMessage('-- documents: ' . $numDocs . ' , size: ' . $size);
								}
								else
								{
									$event->addCommentMessage('-- Not defined on client.');
								}
							}
						}
						elseif (($indexName = $event->getParam('indexName')) != null)
						{
							$indexDef = $indexManager->findIndexDefinitionByName($name, $indexName);
							if ($indexDef)
							{
								if ($event->getParam('delete'))
								{
									$indexManager->deleteIndex($indexDef);
									$event->addInfoMessage('index: "' . $name . '/' . $indexName . '" deleted');
								}
								if ($event->getParam('create'))
								{
									$index = $indexManager->setIndexConfiguration($indexDef);
									if ($index)
									{
										$event->addInfoMessage('index: "' . $name . '/' . $indexName . '" created');
									}
									else
									{
										$event->addErrorMessage('index: "' . $name . '/' . $indexName . '" not created');
									}
								}
							}
							else
							{
								$event->addErrorMessage('index "' . $name . '/' . $indexName . '" not found.');
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
			$clientsName = $indexManager->getClientsName();
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
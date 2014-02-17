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
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			$response->addErrorMessage('Generic services not registered');
			return;
		}

		$indexManager = $genericServices->getIndexManager();
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
						$response->addInfoMessage('Server: ' . $srvStat['name'] . ' (' . $srvStat['version']['number'] . ') is ok ('
							. $srvStat['status'] . ')');
						if ($event->getParam('list'))
						{
							foreach ($indexManager->getIndexesDefinition($name) as $indexDef)
							{
								$response->addInfoMessage('Declared index "' . $indexDef->getName() . '", mapping: '
									. $indexDef->getMappingName()
									. ', language: ' . $indexDef->getAnalysisLCID());
								$idx = $client->getIndex($indexDef->getName());
								if ($idx->exists())
								{
									$status = $idx->getStatus();
									$numDocs = $status->get('docs')['num_docs'];
									$size = $status->get('index')['size'];
									$response->addInfoMessage('-- documents: ' . $numDocs . ' , size: ' . $size);
								}
								else
								{
									$response->addCommentMessage('-- Not defined on client.');
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
									$response->addInfoMessage('index: "' . $indexName . '" deleted');
								}
								if ($event->getParam('create'))
								{
									$index = $indexManager->setIndexConfiguration($indexDef);
									if ($index)
									{
										$response->addInfoMessage('index: "' . $indexName . '" created');
									}
									else
									{
										$response->addErrorMessage('index: "' . $indexName . '" not created');
									}
								}

								if ($event->getParam('facet-mapping') || $event->getParam('create'))
								{
									$mapping = $genericServices->getFacetManager()->getIndexMapping($indexDef);
									if (count($mapping))
									{
										$indexManager->setFacetMapping($indexDef, $mapping);
										$response->addInfoMessage('index: "' . $indexName . '" facet mapping updated');
									}
									elseif ($event->getParam('facet-mapping'))
									{
										$response->addCommentMessage('index: "' . $indexName . '" has no facet to update');
									}
								}
							}
							else
							{
								$response->addErrorMessage('index "' . $indexName . '" not found.');
							}
						}
					}
					else
					{
						$response->addErrorMessage('Error: ' . print_r($srvStat, true));
					}
				}
				catch (\Exception $e)
				{
					$applicationServices->getLogging()->exception($e);
					$response->addErrorMessage('Error on client ' . $name . ': ' . $e->getMessage());
				}
			}
			else
			{
				$response->addErrorMessage('Invalid client name: ' . $name);
			}
		}
		else
		{
			$clientsName = $indexManager->getClientsName();
			if (count($clientsName))
			{
				$response->addInfoMessage('Declared clients: ' . implode(', ', $clientsName));
			}
			else
			{
				$response->addCommentMessage('No declared client.');
			}
		}
	}
}
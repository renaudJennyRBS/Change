<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		if (is_string($clientName = $event->getParam('name')))
		{
			$client = $indexManager->getElasticaClient($clientName);
			if ($client)
			{
				try
				{
					$status = $client->getStatus();
					$srvStat = $status->getServerStatus();
					if (isset($srvStat['status']) && $srvStat['status'] == 200)
					{
						$response->addInfoMessage('Server: ' . $srvStat['name'] . ' (' . $srvStat['version']['number'] . ') is ok ('
							. $srvStat['status'] . ')');

						if ($event->getParam('list'))
						{
							foreach ($indexManager->getIndexesDefinition($clientName) as $indexDef)
							{
								$response->addInfoMessage('Declared index "' . $indexDef->getName() .'"');
								$idx = $client->getIndex($indexDef->getName());
								if ($idx->exists())
								{
									$status = $idx->getStatus();
									$numDocs = $status->get('docs')['num_docs'];
									$size = ($status->get('index')['size_in_bytes'] / 1024) . ' kb';
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
							$indexDef = $indexManager->findIndexDefinitionByName($clientName, $indexName);
							if ($indexDef)
							{
								if ($event->getParam('delete'))
								{
									$indexManager->deleteIndex($indexDef);
									$response->addInfoMessage('index: "' . $indexName . '" deleted');
								}
								if ($event->getParam('create'))
								{
									$index = $indexManager->createIndex($indexDef);
									if ($index && $index->exists())
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
									$facetsMappings = $genericServices->getFacetManager()->getIndexMapping($indexDef);
									if (count($facetsMappings))
									{
										$indexManager->updateFacetsMappings($indexDef, $facetsMappings);
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
					$response->addErrorMessage('Error on client ' . $clientName . ': ' . $e->getMessage());
				}
			}
			else
			{
				$response->addErrorMessage('Invalid client name: ' . $clientName);
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
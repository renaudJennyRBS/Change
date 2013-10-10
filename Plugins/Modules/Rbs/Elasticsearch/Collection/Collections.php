<?php
namespace Rbs\Elasticsearch\Collection;

use Change\I18n\I18nString;


/**
 * @name \Rbs\Elasticsearch\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addClients(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
			$indexManager->setDocumentServices($documentServices);

			$items = array();
			foreach ($indexManager->getClientsName() as $clientName)
			{
				$items[$clientName] = $clientName;
				$client = $indexManager->getClient($clientName);
				if ($client)
				{
					try
					{
						$serverStatus = $client->getStatus()->getServerStatus();

						if (isset($serverStatus['ok']) && $serverStatus['ok'])
						{
							$items[$clientName] .= ' (' . $serverStatus['name'] .', ' . $serverStatus['version']['number'] . ')';
						}
						else
						{
							$items[$clientName] .= ' (' . print_r($serverStatus, true). ')';
						}
					}
					catch (\Exception $e)
					{
						$items[$clientName] .= ' (' . $e->getMessage(). ')';
					}

				}
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Elasticsearch_Collection_Clients', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
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
		try
		{
			if ($event->getParam('list'))
			{
				$clientsName = $im->getClientsName();
				$event->addInfoMessage('Declared clients: ' . implode(', ', $clientsName));
			}
			elseif (is_string($name = $event->getParam('name')))
			{
				$client = $im->getClient($name);
				$status = $client->getStatus();
				$srvStat = $status->getServerStatus();
				if ($srvStat['ok'])
				{
					$event->addInfoMessage('Server: '. $srvStat['name'] .' ('. $srvStat['version']['number'] .') is ok ('. $srvStat['status'] .')');
					$event->addInfoMessage('  Index names: '. implode(', ', $status->getIndexNames()));
				}
				else
				{
					$event->addErrorMessage('Error: '. print_r($srvStat, true));
				}

			}
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$event->addErrorMessage('Exception: ' . $e->getMessage());
		}
	}
}
<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\GenerateDbSchema
 */
class GenerateDbSchema
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$generator = new \Change\Db\Schema\Generator($application->getWorkspace(), $applicationServices->getDbProvider());
		try 
		{
			if ($event->getParam('with-modules'))
			{
				$generator->generate();
				$response->addInfoMessage('Change and Modules DB schema generated.');
			}
			else
			{
				$generator->generateSystemSchema();
				$response->addInfoMessage('Change DB schema generated (to generate Modules DB schema, add -m option).');
			}
		} 
		catch (\Exception $e )
		{
			$applicationServices->getLogging()->exception($e);
			$response->addErrorMessage($e->getMessage());
		}
	}
}
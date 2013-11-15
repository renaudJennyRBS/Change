<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\ManageCache
 */
class ManageCache
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$activate = $event->getParam('activate');
		$deactivate = $event->getParam('deactivate');
		$currentValues = $application->getConfiguration()->getEntry('Change/Cache');

		$response = $event->getCommandResponse();

		if ($activate || $deactivate)
		{
			$updated = false;
			$editConfig = new \Change\Configuration\EditableConfiguration(array());
			$editConfig->import($application->getConfiguration());
			foreach (explode(',', $activate) as $name)
			{
				$name = trim($name);
				if (!isset($currentValues[$name]))
				{
					continue;
				}
				if (!$currentValues[$name])
				{
					$editConfig->addPersistentEntry('Change/Cache/'. $name, true, \Change\Configuration\Configuration::PROJECT);
					$response->addInfoMessage('Cache "'.$name.'": activated');
					$updated = true;
				}
			}
			foreach (explode(',', $deactivate) as $name)
			{
				$name = trim($name);
				if (!isset($currentValues[$name]))
				{
					continue;
				}
				if ($currentValues[$name])
				{
					$editConfig->addPersistentEntry('Change/Cache/'. $name, false, \Change\Configuration\Configuration::PROJECT);
					$response->addInfoMessage('Cache "'.$name.'": deactivated');
					$updated = true;
				}
			}

			if ($updated)
			{
				$editConfig->save();
				$response->addInfoMessage('Configuration saved');
			}
		}
		else
		{
			$response->addInfoMessage('Resume:');
			foreach ($currentValues as $name => $state)
			{
				$response->addInfoMessage(' - Cache "'.$name.'": ' .($state ? 'activated' : 'deactivated'));
			}
		}
	}
}
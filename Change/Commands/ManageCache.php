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
		$applicationServices = new \Change\Application\ApplicationServices($application);
		$activate = $event->getParam('activate');
		$deactivate = $event->getParam('deactivate');
		$currentValues = $application->getConfiguration()->getEntry('Change/Cache');

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
					$event->addInfoMessage('Cache "'.$name.'": activated');
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
					$event->addInfoMessage('Cache "'.$name.'": deactivated');
					$updated = true;
				}
			}

			if ($updated)
			{
				$editConfig->save();
				$event->addInfoMessage('Configuration saved');
			}
		}
		else
		{
			$event->addInfoMessage('Resume:');
			foreach ($currentValues as $name => $state)
			{
				$event->addInfoMessage(' - Cache "'.$name.'": ' .($state ? 'activated' : 'deactivated'));
			}
		}
	}
}
<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\UpdateCurrentUser
 */
class UpdateCurrentUser
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$profileManager = $event->getApplicationServices()->getProfileManager();
		$props = $event->getRequest()->getPost()->toArray();

		$profile = $profileManager->loadProfile($user, 'Change_User');
		if ($profile)
		{
			$save = false;
			foreach ($profile->getPropertyNames() as $name)
			{
				if (isset($props[$name]))
				{
					$profile->setPropertyValue($name, $props[$name]);
					$save = true;
				}
			}
			if ($save)
			{
				$profileManager->saveProfile($user, $profile);
			}
		}

		$profile = $profileManager->loadProfile($user, 'Rbs_Admin');
		if ($profile)
		{
			$save = false;
			foreach ($profile->getPropertyNames() as $name)
			{
				if (isset($props[$name]))
				{
					$profile->setPropertyValue($name, $props[$name]);
					$save = true;
				}
			}
			if ($save)
			{
				$profileManager->saveProfile($user, $profile);
			}
		}

		(new GetCurrentUser())->execute($event);
	}
}
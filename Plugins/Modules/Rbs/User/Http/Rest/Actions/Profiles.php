<?php
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;
use Change\User\ProfileManager;

/**
 * @name \Rbs\User\Http\Rest\Actions\Profiles
 */
class Profiles
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$pm = new ProfileManager();
		$pm->setDocumentServices($event->getDocumentServices());
		$user = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$data = [];
		if ($user instanceof \Rbs\User\Documents\User)
		{
			foreach ($pm->getProfileNames() as $profileName)
			{
				$profile = $pm->loadProfile($user, $profileName);
				if ($profile instanceof \Change\User\ProfileInterface)
				{
					$data[$profileName] = [];
					foreach ($profile->getPropertyNames() as $name)
					{
						$data[$profileName][$name] = $profile->getPropertyValue($name);
					}
				}
			}
		}
		$result->setArray($data);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}
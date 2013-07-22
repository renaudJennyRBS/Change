<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\DocumentResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\GetCurrentUser
 */
class GetCurrentUser
{

	/**
	 * TODO WWW-Authenticate: OAuth realm="Rbs_Admin"
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{

		$result = new DocumentResult();
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$properties = array(
			'id' => $user->getId(),
			'pseudonym' => $user->getName()
		);

		$result->setProperties($properties);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);


		$profileManager = new \Change\User\ProfileManager();
		$profileManager->setDocumentServices($event->getDocumentServices());
		$props = array();
		$profile = $profileManager->loadProfile($user, 'Change_User');
		if ($profile)
		{
			foreach ($profile->getPropertyNames() as $name)
			{
				$props[$name] = $profile->getPropertyValue($name);
				if (!isset($props[$name]))
				{
					if ($name === 'LCID')
					{
						$props[$name] = $event->getApplicationServices()->getI18nManager()->getLCID();
					}
					elseif ($name === 'TimeZone')
					{
						$tz =  $event->getApplicationServices()->getI18nManager()->getTimeZone();
						$props[$name] = $tz->getName();
					}
				}
			}
		}

		$profile = $profileManager->loadProfile($user, 'Rbs_Admin');
		if ($profile)
		{
			foreach ($profile->getPropertyNames() as $name)
			{
				$props[$name] = $profile->getPropertyValue($name);
			}
		}
		$result->setProperty('profile', $props);
		$event->setResult($result);
	}
}
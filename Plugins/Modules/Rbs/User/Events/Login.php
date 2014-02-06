<?php
namespace Rbs\User\Events;

use Change\Events\Event;

/**
 * @name \Rbs\User\Events\Login
 */
class Login
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if (!$applicationServices)
		{
			return;
		}

		if ($event->getParam('userId'))
		{
			$user = $applicationServices->getDocumentManager()->getDocumentInstance($event->getParam('userId'));
			if ($user instanceof \Rbs\User\Documents\User)
			{
				$event->setParam('user', new AuthenticatedUser($user));
			}
			return;
		}

		$realm = $event->getParam('realm');
		$login = $event->getParam('login');
		$password = $event->getParam('password');

		if (!is_string($realm) || empty($realm)
			|| !is_string($login) || empty($login)
			|| !is_string($password) || empty($password)
		)
		{
			return;
		}

		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_User');
		$groupBuilder = $query->getPropertyBuilder('groups');
		$or = $query->getFragmentBuilder()->logicOr($query->eq('login', $login), $query->eq('email', $login));
		$query->andPredicates($query->activated(), $or, $groupBuilder->eq('realm', $realm));

		$collection = $query->getDocuments();
		foreach ($collection as $document)
		{
			/* @var $document \Rbs\User\Documents\User */
			if ($document->checkPassword($password))
			{
				$event->setParam('user', new AuthenticatedUser($document));
				return;
			}
		}
	}
}
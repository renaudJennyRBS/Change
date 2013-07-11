<?php
namespace Rbs\User\Events;

use Change\Documents\DocumentServices;
use Change\Documents\Query\Query;
use Zend\EventManager\Event;

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
		$documentServices = $event->getParam('documentServices');
		if (!$documentServices instanceof DocumentServices)
		{
			return;
		}

		if ($event->getParam('userId'))
		{
			$user = $documentServices->getDocumentManager()->getDocumentInstance($event->getParam('userId'));
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

		$query = new Query($documentServices, 'Rbs_User_User');
		$groupBuilder = $query->getPropertyBuilder('groups');
		$query->andPredicates($query->published(), $query->eq('login', $login), $groupBuilder->eq('realm', $realm));

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
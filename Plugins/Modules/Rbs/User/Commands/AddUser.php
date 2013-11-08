<?php
namespace Rbs\User\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\User\Commands\AddUser
 */
class AddUser
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{

		$as = $event->getApplicationServices();
		$login = $event->getParam('login');
		$password = $event->getParam('password', \Change\Stdlib\String::random());
		$email = $event->getParam('email');
		$realms = explode(',', $event->getParam('realms', ''));

		$query = $as->getDocumentManager()->getNewQuery('Rbs_User_User');
		$user = $query->andPredicates($query->eq('login', $login))->getFirstDocument();
		if (!$user)
		{
			$transactionManager = $as->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$query = $as->getDocumentManager()->getNewQuery('Rbs_User_Group');
				$groups = $query->andPredicates($query->in('realm', $realms))->getDocuments();

				/* @var $user \Rbs\User\Documents\User */
				$user = $as->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
				$user->setLabel($email);
				$user->setEmail($email);
				$user->setLogin($login);
				$user->setPassword($password);
				$user->setActive(true);
				$user->setGroups($groups->toArray());
				$user->create();

				if ($event->getParam('is-root') == true)
				{
					$pm = $as->getPermissionsManager();
					if (!$pm->hasRule($user->getId()))
					{
						$pm->addRule($user->getId());
					}
				}

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
			$event->addInfoMessage('User successfuly created');
			$event->addCommentMessage('login: ' . $login);
			$event->addCommentMessage('password: ' . $password);
		}
		else
		{
			$event->addErrorMessage('User ' . $login . ' already exists');
		}
	}
}
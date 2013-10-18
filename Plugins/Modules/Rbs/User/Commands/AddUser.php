<?php
namespace Rbs\User\Commands;

use Change\Application\ApplicationServices;
use Change\Commands\Events\Event;
use Change\Documents\DocumentServices;

/**
 * @name \Rbs\User\Commands\AddUser
 */
class AddUser
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$as = new ApplicationServices($application);
		$ds = new DocumentServices($as);


		$login = $event->getParam('login');
		$password = $event->getParam('password', \Change\Stdlib\String::random());
		$email = $event->getParam('email');
		$realms = explode(',', $event->getParam('realms', ''));

		$query = new \Change\Documents\Query\Query($ds, 'Rbs_User_User');
		$user = $query->andPredicates($query->eq('login', $login))->getFirstDocument();
		if (!$user)
		{
			$transactionManager = $as->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$query = new \Change\Documents\Query\Query($ds, 'Rbs_User_Group');
				$groups = $query->andPredicates($query->in('realm', $realms))->getDocuments();

				/* @var $user \Rbs\User\Documents\User */
				$user = $ds->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
				$user->setLabel($login);
				$user->setEmail($email);
				$user->setLogin($login);
				$user->setPassword($password);
				$user->setIdentifier($login);
				$user->setActive(true);
				$user->setGroups($groups->toArray());
				$user->create();

				if ($event->getParam('is-root') == true)
				{
					$pm = new \Change\Permissions\PermissionsManager();
					$pm->setApplicationServices($as);
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
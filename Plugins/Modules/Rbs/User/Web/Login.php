<?php
namespace Rbs\User\Web;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;
use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;

/**
* @name \Rbs\User\Events\Login
*/
class Login
{
	/**
	 * @param Event $event
	 */
	public static function executeByName(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$a = new self();
			$a->authenticate($event);
		}
	}

	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if ($request->getMethod() === 'POST')
		{
			if (strpos($request->getPath(), 'Action/Rbs/User/HttpLogin') !== false)
			{
				$this->authenticate($event);
			}
		}
	}

	public function authenticate(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$datas = $event->getRequest()->getPost()->toArray();
			$realm = $datas['realm'];
			$login = $datas['login'];
			$password = $datas['password'];
			if ($realm && $login && $password)
			{
				$am = new \Change\User\AuthenticationManager();
				$am->setSharedEventManager($event->getApplicationServices()->getApplication()->getSharedEventManager());
				$am->setDocumentServices($event->getDocumentServices());
				$user = $am->login($login, $password, $realm);
				if ($user instanceof \Rbs\User\Documents\User)
				{
					$accessorId = $user->getId();
					$authentication = new \Change\Http\Web\Authentication();
					$authentication->save($website, $accessorId);
					$event->setAuthentication($authentication);
					$datas = array('accessorId' => $accessorId);
					$datas['pseudonym'] = $user->getPseudonym();
					$datas['email'] = $user->getEmail();
				}
				else
				{
					$datas['error'] = 'Unable to Authenticate';
				}
			}
			else
			{
				$datas['error'] = 'Invalid parameters';
			}
		}
		else
		{
			$datas = array('error' => 'Invalid website');
		}

		$result = new \Change\Http\Web\Result\AjaxResult($datas);
		$event->setResult($result);
	}


	/**
	 * @param string $realm
	 * @param string $login
	 * @param string $password
	 * @param DocumentManager $documentManager
	 * @return integer|null
	 */
	protected function findAccessorId($realm, $login, $password, DocumentManager $documentManager)
	{

		$qb = new \Change\Documents\Query\Query($documentManager->getDocumentServices(), 'Rbs_User_User');
		$qb1 = $qb->getPropertyBuilder('groups');
		$qb->andPredicates($qb->eq('login', $login), $qb->published(), $qb1->eq('realm', $realm));
		$collection = $qb->getDocuments();

		foreach ($collection as $document)
		{
			if ($document instanceof \Rbs\User\Documents\User)
			{
				if ($document->published() && $document->checkPassword($password))
				{
					return $document->getId();
				}
			}
		}
		return null;
	}
}
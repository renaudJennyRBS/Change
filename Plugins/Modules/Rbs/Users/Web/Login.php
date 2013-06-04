<?php
namespace Rbs\Users\Web;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;
use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;

/**
* @name \Rbs\Users\Events\Login
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
			if (strpos($request->getPath(), 'Action/Rbs/Users/HttpLogin') !== false)
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
				$accessorId = $this->findAccessorId($realm, $login, $password, $event->getDocumentServices()->getDocumentManager());
				if ($accessorId)
				{
					$authentication = new \Change\Http\Web\Authentication();
					$authentication->save($website,$accessorId);
					$event->setAuthentication($authentication);
					$datas = array('accessorId' => $accessorId);
					$user = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($accessorId);
					if ($user instanceof \Rbs\Users\Documents\User)
					{
						$datas['pseudonym'] = $user->getPseudonym();
						$datas['email'] = $user->getEmail();
					}
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

		$qb = new \Change\Documents\Query\Builder($documentManager->getDocumentServices(), 'Rbs_Users_User');
		$qb1 = $qb->getPropertyBuilder('groups');
		$qb->andPredicates($qb->eq('login', $login), $qb->published(), $qb1->eq('realm', $realm));
		$collection = $qb->getDocuments();

		foreach ($collection as $document)
		{
			if ($document instanceof \Rbs\Users\Documents\User)
			{
				if ($document->getPublishableFunctions()->published() && $document->checkPassword($password))
				{
					return $document->getId();
				}
			}
		}
		return null;
	}
}
<?php
namespace Rbs\User\Http\Web;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\Login
*/
class Login
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 */
	public static function executeByName(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$a = new self();
			$a->login($event);
		}
	}

	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if ($request->getMethod() === 'POST')
		{
			if (strpos($request->getPath(), 'Action/Rbs/User/Login') !== false)
			{
				$this->login($event);
			}
		}
	}

	public function login(Event $event)
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
				$am = $event->getAuthenticationManager();
				$user = $am->login($login, $password, $realm);
				if ($user instanceof \Change\User\UserInterface)
				{
					$am->setCurrentUser($user);
					$accessorId = $user->getId();
					$this->save($website, $accessorId);
					$datas = array('accessorId' => $accessorId, 'name' => $user->getName());
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
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param $accessorId
	 */
	public function save(\Change\Presentation\Interfaces\Website $website, $accessorId)
	{
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if ($accessorId === null || $accessorId === false)
		{
			unset($session[$website->getId()]);
		}
		else
		{
			$session[$website->getId()] = $accessorId;
		}
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return integer|null
	 */
	public function load(\Change\Presentation\Interfaces\Website $website)
	{
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if (isset($session[$website->getId()]))
		{
			return $session[$website->getId()];
		}
		return null;
	}

	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function authenticate(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$accessorId = $this->load($website);
			if (is_int($accessorId))
			{
				$user = $event->getAuthenticationManager()->getById($accessorId);
				if ($user instanceof \Change\User\UserInterface)
				{
					$event->getAuthenticationManager()->setCurrentUser($user);
				}
				else
				{
					throw new \RuntimeException('Invalid AccessorId: ' . $accessorId, 999999);
				}
			}
		}
	}
}
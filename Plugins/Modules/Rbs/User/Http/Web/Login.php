<?php
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\Login
*/
class Login extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$this->login($event);
		}
	}

	/**
	 * @param Event $event
	 */
	public function login(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$data = $event->getRequest()->getPost()->toArray();
			$realm = $data['realm'];
			$login = $data['login'];
			$password = $data['password'];
			if ($realm && $login && $password)
			{
				$am = $event->getAuthenticationManager();
				$user = $am->login($login, $password, $realm);
				if ($user instanceof \Change\User\UserInterface)
				{
					$am->setCurrentUser($user);
					$accessorId = $user->getId();
					$this->save($website, $accessorId);
					$data = array('accessorId' => $accessorId, 'name' => $user->getName());
				}
				else
				{
					$data['error'] = 'Unable to Authenticate';
				}
			}
			else
			{
				$data['error'] = 'Invalid parameters';
			}
		}
		else
		{
			$data = array('error' => 'Invalid website');
		}

		$result = new \Change\Http\Web\Result\AjaxResult($data);
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
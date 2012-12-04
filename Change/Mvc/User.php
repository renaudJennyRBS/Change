<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\User
 */
class User
{
	const BACKEND_NAMESPACE = 'backend';
	const FRONTEND_NAMESPACE = 'frontend';
	
	const AUTH_NAMESPACE = 'User/authenticated';

	// extends ParameterHolder
	const ATTRIBUTE_NAMESPACE = 'User/attributes';

	/**
	 * @var straing
	 */
	protected $userNamespace = self::FRONTEND_NAMESPACE;

	/**
	 * @var \Change\Mvc\Context
	 */
	protected $context = null;

	/**
	 * @var array
	 */
	protected $authenticated;
	
	/**
	 * @return \Change\Mvc\Context
	 */
	public function getContext()
	{
		return $this->context;
	}
	
	/**
	 * @param \Change\Mvc\Context $context
	 * @param array $parameters
	 */
	public function initialize($context, $parameters = null)
	{	
		$this->context = $context;
	}
	
	/**
	 */
	public function shutdown()
	{	
		$this->context = null;
	}
		
	/**
	 * @param string $userNamespace
	 * @return string Old namespace
	 */
	public function setUserNamespace($userNamespace)
	{
		if ($userNamespace !== self::BACKEND_NAMESPACE)
		{
			$userNamespace = self::FRONTEND_NAMESPACE;
		}
		
		$oldNamespace = $this->userNamespace;
		$this->userNamespace = $userNamespace;
		return $oldNamespace;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getUserNamespace()
	{
		return $this->userNamespace;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->context->getStorage()->readForUser('framework_login'); 
	}
	
	/**
	 * @param string $login
	 */
	public function setLogin($login)
	{
		$this->context->getStorage()->writeForUser('framework_login', $login); 
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->context->getStorage()->readForUser('framework_userid');
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->context->getStorage()->writeForUser('framework_userid', $id);
	}
	
	/**
	 * Initializes the User using a modules_users/user.
	 * @param users_persistentdocument_user $user
	 */
	public function setUser($user)
	{
		//TODO Old class Usage
		if ($user instanceof \users_persistentdocument_user) 
		{
			$isRoot = $user->getIsroot();
			$this->context->getStorage()->writeForUser('framework_isRoot', $isRoot);
			$this->setLogin($user->getLogin());
			$this->setId($user->getId());
		}
	}
	
	/**
	 * Get the superuser attribute for the user. 
	 *
	 * @return boolean true if super user false otherwise.
	 */
	public function isRoot()
	{
		return $this->context->getStorage()->readForUser('framework_isRoot') === true;
	}
		
	/**
	 * @return Booolean
	 */
	public function isAuthenticated()
	{
		$data = $this->context->getStorage()->readForUser('framework_isAuthenticated');
		return $data == true;
	}
	
	/**
	 * @param boolean $authenticated
	 */
	public function setAuthenticated($authenticated)
	{
		if ($authenticated === true)
		{
			$this->context->getStorage()->writeForUser('framework_isAuthenticated', $authenticated);
		}
		else
		{
			$this->context->getStorage()->removeForUser('framework_isAuthenticated');
		}
	}
}
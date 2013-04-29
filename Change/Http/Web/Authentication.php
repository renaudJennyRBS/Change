<?php
namespace Change\Http\Web;

use Change\Http\AuthenticationInterface;
use Change\Presentation\Interfaces\Website;

/**
 * @name \Change\Http\Web\Authentication
 */
class Authentication implements AuthenticationInterface
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @var integer
	 */
	protected $accessorId = false;

	/**
	 * @param Website $website
	 */
	function __construct(Website $website = null)
	{
		if ($website !== null)
		{
			$this->load($website);
		}
	}

	/**
	 * @param integer $accessorId
	 */
	public function setAccessorId($accessorId)
	{
		$this->accessorId = $accessorId;
	}

	/**
	 * @return integer
	 */
	public function getAccessorId()
	{
		return $this->accessorId;
	}

	/**
	 * @return boolean
	 */
	public function isAuthenticated()
	{
		return (false !== $this->accessorId);
	}

	/**
	 * @return mixed|null
	 */
	public function getIdentity()
	{
		if ($this->isAuthenticated())
		{
			return $this->accessorId;
		}
		return null;
	}

	/**
	 * @param Website $website
	 */
	public function load(Website $website)
	{
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if (isset($session[$website->getId()]))
		{
			$this->accessorId = $session[$website->getId()];
		}
	}

	/**
	 * @param Website $website
	 * @param $accessorId
	 */
	public function save(Website $website, $accessorId)
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
}
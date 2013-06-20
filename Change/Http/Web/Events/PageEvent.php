<?php
namespace Change\Http\Web\Events;

use Change\Application\ApplicationServices;
use Change\Presentation\Interfaces\Page;
use Zend\EventManager\Event;
use Change\Permissions\PermissionsManager;
use Change\Presentation\PresentationServices;
use Change\User\AuthenticationManager;

/**
 * @name \Change\Http\Web\Events\PageEvent
 */
class PageEvent extends Event
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var \Change\Http\Web\Result\Page
	 */
	protected $pageResult;

	/**
	 * @var \Change\Http\Request
	 */
	protected $request;

	/**
	 * @var AuthenticationManager
	 */
	protected $authenticationManager;

	/**
	 * @var PermissionsManager
	 */
	protected $permissionsManager;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @api
	 * @return ApplicationServices|null
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param PresentationServices|null $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices = null)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @api
	 * @return PresentationServices|null
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param AuthenticationManager $authenticationManager
	 */
	public function setAuthenticationManager($authenticationManager)
	{
		$this->authenticationManager = $authenticationManager;
	}

	/**
	 * @return AuthenticationManager
	 */
	public function getAuthenticationManager()
	{
		return $this->authenticationManager;
	}

	/**
	 * @param \Change\Permissions\PermissionsManager $permissionsManager
	 */
	public function setPermissionsManager($permissionsManager)
	{
		$this->permissionsManager = $permissionsManager;
	}

	/**
	 * @return \Change\Permissions\PermissionsManager
	 */
	public function getPermissionsManager()
	{
		return $this->permissionsManager;
	}

	/**
	 * @param \Change\Http\Web\Result\Page $pageResult
	 */
	public function setPageResult($pageResult)
	{
		$this->pageResult = $pageResult;
	}

	/**
	 * @return \Change\Http\Web\Result\Page
	 */
	public function getPageResult()
	{
		return $this->pageResult;
	}

	/**
	 * @param \Change\Http\Request $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}

	/**
	 * @return \Change\Http\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @return Page|null
	 */
	public function getPage()
	{
		if ($this->getTarget() instanceof Page)
		{
			return $this->getTarget();
		}
		return null;
	}
}
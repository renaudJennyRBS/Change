<?php
namespace Change\Http\Web\Events;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
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
	 * @var DocumentServices
	 */
	protected $documentServices;

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
	 * @return $this
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
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
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}



	/**
	 * @param PresentationServices|null $presentationServices
	 * @return $this
	 */
	public function setPresentationServices(PresentationServices $presentationServices = null)
	{
		$this->presentationServices = $presentationServices;
		return $this;
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
	 * @return $this
	 */
	public function setAuthenticationManager($authenticationManager)
	{
		$this->authenticationManager = $authenticationManager;
		return $this;
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
	 * @return $this
	 */
	public function setPermissionsManager($permissionsManager)
	{
		$this->permissionsManager = $permissionsManager;
		return $this;
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
	 * @return $this
	 */
	public function setPageResult($pageResult)
	{
		$this->pageResult = $pageResult;
		return $this;
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
	 * @return $this
	 */
	public function setRequest($request)
	{
		$this->request = $request;
		return $this;
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
	 * @return $this
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
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
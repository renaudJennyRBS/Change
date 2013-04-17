<?php
namespace Change\Http\Web\Events;

use Change\Presentation\Interfaces\Page;
use Zend\EventManager\Event;

/**
 * @name \Change\Http\Web\Events\PageEvent
 */
class PageEvent extends Event
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Presentation\PresentationServices
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
	 * @var \Change\Http\AuthenticationInterface
	 */
	protected $authentication;

	/**
	 * @var \Change\Http\AclInterface
	 */
	protected $acl;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;


	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @api
	 * @return \Change\Application\ApplicationServices|null
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Presentation\PresentationServices|null $presentationServices
	 */
	public function setPresentationServices(\Change\Presentation\PresentationServices $presentationServices = null)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @api
	 * @return \Change\Presentation\PresentationServices|null
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param \Change\Http\AclInterface $acl
	 */
	public function setAcl($acl)
	{
		$this->acl = $acl;
	}

	/**
	 * @return \Change\Http\AclInterface
	 */
	public function getAcl()
	{
		return $this->acl;
	}

	/**
	 * @param \Change\Http\AuthenticationInterface $authentication
	 */
	public function setAuthentication($authentication)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @return \Change\Http\AuthenticationInterface
	 */
	public function getAuthentication()
	{
		return $this->authentication;
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
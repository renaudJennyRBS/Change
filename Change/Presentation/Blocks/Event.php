<?php
namespace Change\Presentation\Blocks;

use Change\Documents\DocumentServices;
use Change\Http\Web\UrlManager;
use Change\Http\Web\Result\BlockResult;
use Change\Presentation\Layout\Block;
use Zend\EventManager\Event as ZendEvent;
use Change\Permissions\PermissionsManager;
use Change\Presentation\PresentationServices;
use Change\User\AuthenticationManager;

/**
 * @name \Change\Presentation\Blocks\Event
 */
class Event extends ZendEvent
{
	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var Block
	 */
	protected $blockLayout;

	/**
	 * @var Parameters
	 */
	protected $blockParameters;

	/**
	 * @var BlockResult;
	 */
	protected $blockResult;

	/**
	 * @var UrlManager
	 */
	protected $urlManager;


	/**
	 * @var AuthenticationManager
	 */
	protected $authenticationManager;

	/**
	 * @var PermissionsManager
	 */
	protected $permissionsManager;


	/**
	 * @param PresentationServices|null $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
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
	 * @param DocumentServices|null $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		if ($this->documentServices === null)
		{
			throw new \RuntimeException('documentServices is not set', 999999);
		}
		return $this->documentServices;
	}

	/**
	 * @param Block $blockLayout
	 * @return $this
	 */
	public function setBlockLayout($blockLayout)
	{
		$this->blockLayout = $blockLayout;
		return $this;
	}

	/**
	 * @api
	 * @return Block
	 */
	public function getBlockLayout()
	{
		return $this->blockLayout;
	}

	/**
	 * @api
	 * @param Parameters $blockParameters
	 * @return $this
	 */
	public function setBlockParameters($blockParameters)
	{
		$this->blockParameters = $blockParameters;
		return $this;
	}

	/**
	 * @api
	 * @return Parameters|null
	 */
	public function getBlockParameters()
	{
		return $this->blockParameters;
	}

	/**
	 * @api
	 * @param BlockResult $blockResult
	 * @return $this
	 */
	public function setBlockResult($blockResult)
	{
		$this->blockResult = $blockResult;
		return $this;
	}

	/**
	 * @api
	 * @return BlockResult|null
	 */
	public function getBlockResult()
	{
		return $this->blockResult;
	}

	/**
	 * @api
	 * @return \Change\Http\Request|null
	 */
	public function getHttpRequest()
	{
		return $this->getParam('httpRequest');
	}

	/**
	 * @param UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
	}

	/**
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
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
	 * @param PermissionsManager $permissionsManager
	 */
	public function setPermissionsManager($permissionsManager)
	{
		$this->permissionsManager = $permissionsManager;
	}

	/**
	 * @return PermissionsManager
	 */
	public function getPermissionsManager()
	{
		return $this->permissionsManager;
	}
}
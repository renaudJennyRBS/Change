<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

use Change\Http\Web\Result\BlockResult;
use Change\Http\Web\UrlManager;
use Change\Permissions\PermissionsManager;
use Change\Presentation\Layout\Block;
use Change\User\AuthenticationManager;

/**
 * @name \Change\Presentation\Blocks\Event
 */
class Event extends \Change\Events\Event
{
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
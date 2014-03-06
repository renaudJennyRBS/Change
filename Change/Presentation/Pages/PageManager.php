<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Pages;

use Change\Http\Web\Result\HtmlHeaderElement;
use Change\Http\Web\Result\Page as PageResult;

/**
 * @name \Change\Presentation\Pages\PageManager
 */
class PageManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'PageManager';

	const EVENT_GET_FUNCTIONS = 'getFunctions';
	const EVENT_GET_CACHE_ADAPTER = 'getCacheAdapter';
	const EVENT_GET_PAGE_RESULT = 'getPageResult';

	/**
	 * @var \Zend\Cache\Storage\Adapter\AbstractAdapter
	 */
	protected $cacheAdapter = false;

	/**
	 * @var \Change\Http\Web\Event
	 */
	protected $httpWebEvent;

	/**
	 * @var \Change\Http\Request
	 */
	protected $request;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Change\User\AuthenticationManager
	 */
	protected $authenticationManager;

	/**
	 * @var \Change\Permissions\PermissionsManager
	 */
	protected $permissionsManager;


	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/PageManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$callback = function (PageEvent $event)
		{
			(new DefaultPageResult())->onGetPageResult($event);
		};
		$eventManager->attach(static::EVENT_GET_PAGE_RESULT, $callback, 5);
	}

	/**
	 * @param \Change\Http\Web\Event $httpWebEvent
	 * @return $this
	 */
	public function setHttpWebEvent(\Change\Http\Web\Event $httpWebEvent = null)
	{
		$this->httpWebEvent = $httpWebEvent;
		if ($httpWebEvent)
		{
			if (null === $this->request)
			{
				$this->setRequest($httpWebEvent->getRequest());
			}
			if (null === $this->urlManager)
			{
				$this->setUrlManager($httpWebEvent->getUrlManager());
			}
			if (null === $this->authenticationManager)
			{
				$this->setAuthenticationManager($httpWebEvent->getAuthenticationManager());
			}
			if (null === $this->permissionsManager)
			{
				$this->setPermissionsManager($httpWebEvent->getPermissionsManager());
			}
		}
		return $this;
	}

	/**
	 * @return \Change\Http\Web\Event
	 */
	public function getHttpWebEvent()
	{
		return $this->httpWebEvent;
	}

	/**
	 * @param \Change\User\AuthenticationManager $authenticationManager
	 * @return $this
	 */
	public function setAuthenticationManager($authenticationManager)
	{
		$this->authenticationManager = $authenticationManager;
		return $this;
	}

	/**
	 * @return \Change\User\AuthenticationManager
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
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
	}

	/**
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @return \Change\Http\Web\Result\Page|null
	 */
	public function getPageResult(\Change\Presentation\Interfaces\Page $page)
	{
		$webEvent = $this->getHttpWebEvent();
		if ($webEvent)
		{
			if ($this->getRequest()->isGet())
			{
				$cacheAdapter = $this->getCacheAdapter();
				if ($cacheAdapter && ($TTL = $page->getTTL()) > 0)
				{
					$uid = $page->getIdentifier() . ' ' . $this->request->getPath() . '' . $this->getRequest()->getQuery()
							->toString();
					$cacheAdapter->getOptions()->setTtl($TTL);
					$key = md5(serialize($uid));
					if ($cacheAdapter->hasItem($key))
					{
						$result = $cacheAdapter->getItem($key);
					}
					else
					{
						$result = $this->dispatchGetPageResult($page);
						$cacheAdapter->addItem($key, $result);
					}
					return $result;
				}
			}
			return $this->dispatchGetPageResult($page);
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		$args = $this->getEventManager()->prepareArgs(['functions' => []]);
		$this->getEventManager()->trigger(static::EVENT_GET_FUNCTIONS, $this, $args);
		$functions = $args['functions'];
		return is_array($functions) ? $functions : [];
	}

	/**
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @return \Change\Http\Web\Result\Page|null
	 */
	protected function dispatchGetPageResult($page)
	{
		$result = new PageResult($page->getIdentifier());
		$result->getHeaders()->addHeaderLine('Content-Type: text/html;charset=utf-8');
		$base = $this->getUrlManager()->getByPathInfo(null)->normalize()->toString();
		$result->addNamedHeadAsString('base', new HtmlHeaderElement('base', array('href' => $base, 'target' => '_self')));

		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array('page' => $page, 'pageResult' => $result));

		$pageEvent = new PageEvent(static::EVENT_GET_PAGE_RESULT, $this, $args);

		$eventManager->trigger($pageEvent);

		return $pageEvent->getPageResult();
	}

	/**
	 * @return \Zend\Cache\Storage\Adapter\AbstractAdapter|null
	 */
	public function getCacheAdapter()
	{
		if (false === $this->cacheAdapter)
		{
			$this->cacheAdapter = null;
			$configuration = $this->getConfiguration();
			if ($configuration->getEntry('Change/Cache/page'))
			{
				$eventManager = $this->getEventManager();
				$event = new \Change\Events\Event(static::EVENT_GET_CACHE_ADAPTER, $this);
				$eventManager->trigger($event);
				$cache = $event->getParam('cacheAdapter');
				if ($cache instanceof \Zend\Cache\Storage\Adapter\AbstractAdapter)
				{
					$this->cacheAdapter = $cache;
				}
			}
		}
		return $this->cacheAdapter;
	}

	/**
	 * @param \Zend\Cache\Storage\Adapter\AbstractAdapter $cacheAdapter
	 * @return $this
	 */
	public function setCacheAdapter(\Zend\Cache\Storage\Adapter\AbstractAdapter $cacheAdapter = null)
	{
		$this->cacheAdapter = $cacheAdapter;
		return $this;
	}
}
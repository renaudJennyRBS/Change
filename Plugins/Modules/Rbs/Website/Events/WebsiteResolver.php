<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Events;

use Change\Http\Web\Event;
use Rbs\Website\Documents\Website;
use Zend\Http\Response;

/**
 * @package Rbs\Website
 * @name \Rbs\Website\Events\WebsiteResolver
 */
class WebsiteResolver
{
	/**
	 * @param Event $event
	 */
	public function resolve($event)
	{
		$data = $this->getWebsiteDatas($event);
		if (count($data))
		{
			$urlManager = $event->getUrlManager();
			$script = $urlManager->getScript();
			$request = $event->getRequest();
			$path = $request->getPath();

			if ($script && strpos($path, $script) === 0)
			{
				$path = ($path === $script) ? null : substr($path, strlen($script));
			}

			$hostName = $request->getUri()->getHost();
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$mm = $event->getApplicationServices()->getModelManager();
			$dm = $event->getApplicationServices()->getDocumentManager();

			$currentWebsite = null;
			foreach ($data as $row)
			{
				if ($hostName === $row['hostName'])
				{
					if ($this->isBasePath($path, $row['pathPart']))
					{
						$model = $mm->getModelByName($row['model']);
						$currentWebsite = $dm->getDocumentInstance(intval($row['id']), $model);
						if ($currentWebsite instanceof Website)
						{
							$LCID = $row['LCID'];
							$i18nManager->setLCID($LCID);
							$request->setLCID($LCID);
							if ($row['pathPart'])
							{
								break;
							}
						}
						else
						{
							$currentWebsite = null;
						}
					}
				}
			}

			if ($currentWebsite === null)
			{
				$cfg = $event->getApplication()->getConfiguration();
				$singleWebsite = $cfg->getEntry('Rbs/Http/Web/SingleWebsite', true);
				if ($singleWebsite)
				{
					$row = 	$data[0];
					$model = $mm->getModelByName($row['model']);
					$currentWebsite = $dm->getDocumentInstance(intval($row['id']), $model);
					if ($currentWebsite instanceof Website)
					{
						$LCID = $currentWebsite->getRefLCID();
						$i18nManager = $event->getApplicationServices()->getI18nManager();
						$i18nManager->setLCID($LCID);
						$request->setLCID($LCID);
					}
				}
			}

			if ($currentWebsite instanceof Website)
			{
				$event->setParam('website', $currentWebsite);
				$urlManager->setWebsite($currentWebsite);
				$stdUrlManager = $currentWebsite->getUrlManager($i18nManager->getLCID());
				if ($stdUrlManager->getScript() != $urlManager->getScript() ||
					$stdUrlManager->getBasePath() || $urlManager->getBasePath() || $hostName != $currentWebsite->getHostName())
				{
					if ($path === '/')
					{
						$result = new \Change\Http\Result(Response::STATUS_CODE_301);
						$result->setHeaderLocation($stdUrlManager->getByPathInfo(''));
						$event->setResult($result);
					}
					elseif ($hostName != $currentWebsite->getHostName())
					{
						$result = new \Change\Http\Result(Response::STATUS_CODE_301);
						$location = $urlManager->getSelf()->setHost($currentWebsite->getHostName())->normalize()->toString();
						$result->setHeaderLocation($location);
						$event->setResult($result);
					}
					else
					{
						$urlManager->setScript($stdUrlManager->getScript());
						$urlManager->setBasePath($stdUrlManager->getBasePath());
						$event->setAction(function(Event $event) {
								$event->setResult($event->getController()->notFound($event));
							});
					}
				}
			}
		}
	}

	/**
	 * @param string $path
	 * @param string $websitePathPart
	 * @return boolean
	 */
	protected function isBasePath($path, $websitePathPart)
	{
		if ($websitePathPart)
		{
			if ($path)
			{
				if ($path[0] == '/') {$path = substr($path, 1);}
				if ($websitePathPart === $path || $websitePathPart .'/' === $path || strpos($path, $websitePathPart . '/') === 0)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * @param \Change\Application $application
	 */
	public function changed($application)
	{
		$cache = $this->getCache($application);
		$cache->removeItem('WebsiteDatas');
	}

	/**
	 * @param Event $event
	 * @return array
	 */
	protected function getWebsiteDatas($event)
	{
		$cache = $this->getCache($event->getApplication());
		$item = $cache->getItem('WebsiteDatas');
		if ($item !== null)
		{
			return unserialize($item);
		}

		$websiteModel =  $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Website_Website');
		if ($websiteModel)
		{
			$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->getDocumentColumn('id'), 'id'),
				$fb->alias($fb->getDocumentColumn('model'), 'model'),
				$fb->alias($fb->getDocumentColumn('LCID'), 'LCID'),
				$fb->alias($fb->getDocumentColumn('hostName'), 'hostName'),
				$fb->alias($fb->getDocumentColumn('pathPart'), 'pathPart'));
			$qb->from($fb->getDocumentI18nTable($websiteModel->getRootName()));
			$qb->innerJoin($fb->getDocumentTable($websiteModel->getRootName()), $fb->getDocumentColumn('id'));
			$qb->andWhere($fb->isNotNull($fb->getDocumentColumn('hostName')));
			$item = $qb->query()->getResults();
		}
		else
		{
			$item = array();
		}
		$cache->setItem('WebsiteDatas', serialize($item));
		return $item;
	}

	/**
	 * @param \Change\Application $application
	 * @return \Zend\Cache\Storage\StorageInterface
	 */
	protected function getCache($application)
	{
		$options = array('cacheDir' => $application->getWorkspace()->cachePath(),
			'namespace' => 'Rbs_Website', 'umask' => false, 'dirPermission' => false, 'filePermission' => false);
		$cache = new \Zend\Cache\Storage\Adapter\Filesystem($options);
		$plugin = new \Zend\Cache\Storage\Plugin\ExceptionHandler(array('throw_exceptions' => false));
		$cache->addPlugin($plugin);
		return $cache;
	}
}
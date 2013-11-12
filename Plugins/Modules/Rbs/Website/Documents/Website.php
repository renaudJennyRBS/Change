<?php
namespace Rbs\Website\Documents;

use Change\Http\Web\UrlManager;
use Zend\Uri\Http;

/**
 * @name \Rbs\Website\Documents\Website
 */
class Website extends \Compilation\Rbs\Website\Documents\Website implements \Change\Presentation\Interfaces\Website
{
	/**
	 * @return string
	 */
	public function getRelativePath()
	{
		return  $this->getPathPart();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite()
	{
		return $this;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return array($this);
	}

	/**
	 * @param \Change\Documents\AbstractDocument $publicationSections
	 * @return $this
	 */
	public function setPublicationSections($publicationSections)
	{
		// TODO: Implement setPublicationSections() method.
		return $this;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setBaseurl($url)
	{
		$currentLocalisation = $this->getCurrentLocalization();
		$url = new Http($url);
		$currentLocalisation->setHostName($url->getHost());
		$currentLocalisation->setPort($url->getPort());
		$fullPath = $url->getPath();
		$index = strpos($fullPath, '.php');
		if ($index !== false)
		{
			$script = substr($fullPath, 0, $index + 4);
			$path = trim(substr($fullPath, $index + 4), '/');
			$currentLocalisation->setPathPart($path ? $path : null);
			$currentLocalisation->setScriptName($script ? $script : null);
		}
		else
		{
			$path = trim($url->getPath(), '/');
			$currentLocalisation->setPathPart($path ? $path : null);
			$currentLocalisation->setScriptName(null);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBaseurl()
	{
		return $this->getUrlManager($this->getLCID())->getByPathInfo('')->normalize()->toString();
	}

	/**
	 * @param string $LCID
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager($LCID)
	{
		$this->getDocumentManager()->pushLCID($LCID);

		$url = new Http();
		$url->setScheme("http");
		$url->setHost($this->getHostName());
		$url->setPort($this->getPort());
		$url->setPath('/');
		$urlManager = new UrlManager($url, $this->getScriptName());
		//TODO TransactionManager not set
		$urlManager->setDbProvider($this->getDbProvider())->setDocumentManager($this->getDocumentManager());
		$urlManager->setWebsite($this);
		$urlManager->setLCID($LCID);
		$urlManager->setBasePath($this->getPathPart());

		$this->getDocumentManager()->popLCID();
		return $urlManager;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_UPDATE, array($this, 'onWebsiteUpdate'), 5);
	}

	public function onCreated(\Change\Documents\Events\Event $event)
	{
		/* @var $website Website */
		$website = $event->getDocument();
		$tm = $event->getApplicationServices()->getTreeManager();
		$parentNode = $tm->getRootNode($website->getDocumentModel()->getTreeName());
		if ($parentNode)
		{
			$tm->insertNode($parentNode, $website);
		}
		$website->setSitemaps($this->defaultSitemaps());
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->getCurrentLCID();
	}

	/**
	 * @return string
	 */
	public function getHostName()
	{
		return $this->getCurrentLocalization()->getHostName();
	}

	/**
	 * @return integer
	 */
	public function getPort()
	{
		return $this->getCurrentLocalization()->getPort();
	}

	/**
	 * @return string
	 */
	public function getScriptName()
	{
		return $this->getCurrentLocalization()->getScriptName();
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @throws \Exception
	 */
	public function onWebsiteUpdate(\Change\Documents\Events\Event $event)
	{
		$jobManager = $event->getApplicationServices()->getJobManager();
		if ($this->getSitemapGeneration())
		{
			$sitemaps = [];
			foreach ($this->getSitemaps() as $sitemap)
			{
				if (!isset($sitemap['jobId']))
				{
					$LCID = $sitemap['LCID'];
					$timeInterval = $sitemap['timeInterval'];
					if ($timeInterval && $LCID && in_array($LCID, $this->getLCIDArray()))
					{
						$job = $jobManager->createNewJob('Rbs_Seo_GenerateSitemap', [
							'websiteId' => $this->getId(),
							'LCID' => $LCID,
							'randomKey' => \Change\Stdlib\String::random()
						]);
						$sitemap['jobId'] = $job->getId();
					}
					else
					{
						throw new \Exception('sitemap generation job cannot be created with websiteId: ' . $this->getId() .
						 ' LCID: ' . $LCID . ' and time interval: ' . $timeInterval, 999999);
					}
				}
				//Notify user for URL creation (if he want it, 'notify' attribute is added to sitemap)
				elseif (isset($sitemap['notify']) && isset($sitemap['url']))
				{
					$this->notifyUserOfSitemapURLCreation($sitemap, $event->getApplicationServices());
					unset($sitemap['notify']);
				}
				$sitemaps[] = $sitemap;
			}
			$this->setSitemaps($sitemaps);
		}
		else
		{
			//stop generation sitemap jobs if exist
			foreach ($this->getSitemaps() as $sitemap)
			{
				if (isset($sitemap['jobId']))
				{
					$job = $jobManager->getJob($sitemap['jobId']);
					if ($job !== null)
					{
						$jobManager->updateJobStatus($job, \Change\Job\JobInterface::STATUS_SUCCESS);
					}
				}
			}
			//back to default sitemaps
			$this->setSitemaps($this->defaultSitemaps());
		}
	}

	/**
	 * @return array
	 */
	protected function defaultSitemaps()
	{
		$sitemaps = [];
		foreach ($this->getLCIDArray() as $LCID)
		{
			$sitemaps[] = ['LCID' => $LCID, 'timeInterval' => ''];
		}
		return $sitemaps;
	}

	/**
	 * @param array $sitemap
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	protected function notifyUserOfSitemapURLCreation($sitemap, $applicationServices)
	{
		$userId = isset($sitemap['notify']) && isset($sitemap['notify']['userId']) ? $sitemap['notify']['userId'] : null;
		if ($userId)
		{
			$user = $applicationServices->getDocumentManager()->getDocumentInstance($userId);
			$LCID = isset($sitemap['LCID']) ? $sitemap['LCID'] : null;
			if ($user instanceof \Rbs\User\Documents\User && $LCID)
			{
				$authenticatedUser = new \Rbs\User\Events\AuthenticatedUser($user);
				$params = [
					'website' => $this->getLabel(),
					'LCID' => $LCID
				];

				$i18nManager = $applicationServices->getI18nManager();
				$profileManager = $applicationServices->getProfileManager();
				$userProfile = $profileManager->loadProfile($authenticatedUser, 'Change_User');
				$userLCID = $userProfile->getPropertyValue('LCID') != null ? $userProfile->getPropertyValue('LCID') : $i18nManager->getDefaultLCID();
				try
				{
					$applicationServices->getDocumentManager()->pushLCID($userLCID);
					$notification = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Notification_Notification');
					/* @var $notification \Rbs\Notification\Documents\Notification */
					$notification->setUserId($user->getId());
					$notification->setCode('website_sitemap_url_creation_' . $this->getId() . '_' . $LCID);
					$notification->getCurrentLocalization()->setMessage($i18nManager->transForLCID($userLCID, 'm.rbs.website.documents.website.notification-sitemap-url-creation', ['ucf'], $params));
					$notification->setParams($params);
					$notification->save();
					$applicationServices->getDocumentManager()->popLCID();
				}
				catch (\Exception $e)
				{
					$applicationServices->getLogging()->fatal($e);
					$applicationServices->getDocumentManager()->popLCID();
				}
			}
		}
	}

}
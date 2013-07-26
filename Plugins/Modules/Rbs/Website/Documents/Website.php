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
	 */
	public function setPublicationSections($publicationSections)
	{
		// TODO: Implement setPublicationSections() method.
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setBaseurl($url)
	{
		$url = new Http($url);
		$this->setHostName($url->getHost());
		$this->setPort($url->getPort());
		$fullPath = $url->getPath();
		$index = strpos($fullPath, '.php');
		if ($index !== false)
		{
			$script = substr($fullPath, 0, $index + 4);
			$path = trim(substr($fullPath, $index + 4), '/');
			$this->setPathPart($path ? $path : null);
			$this->setScriptName($script ? $script : null);
		}
		else
		{
			$path = trim($url->getPath(), '/');
			$this->setPathPart($path ? $path : null);
			$this->setScriptName(null);
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
		$urlManager->setWebsite($this);
		$urlManager->setLCID($LCID);
		$urlManager->setBasePath($this->getPathPart());
		$urlManager->setDocumentServices($this->getDocumentServices());

		$this->getDocumentManager()->popLCID();
		return $urlManager;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$callback = function(\Change\Documents\Events\Event $event)
		{
			/* @var $website Website */
			$website = $event->getDocument();
			$tm = $website->getDocumentServices()->getTreeManager();
			$parentNode = $tm->getRootNode($website->getDocumentModel()->getTreeName());
			if ($parentNode)
			{
				$tm->insertNode($parentNode, $website);
			}
		};
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, $callback);
	}
}
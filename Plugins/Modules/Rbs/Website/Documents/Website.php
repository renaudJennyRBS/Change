<?php
namespace Rbs\Website\Documents;

use Change\Http\Web\PathRule;
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
	 * @return string
	 */
	public function getBaseurl()
	{
		return $this->getUrlManager()->absoluteUrl(true)->getDefaultByDocument($this)->normalize()->toString();
	}

	/**
	 * @param string $url
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
	}

	/**
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		$url = new Http();
		$url->setScheme("http");
		$url->setHost($this->getHostName());
		$url->setPort($this->getPort());
		$url->setPath($this->getScriptName() . '/' . $this->getPathPart());
		$urlManager = new UrlManager($url, $this->getScriptName());
		$urlManager->setPathRule(new PathRule($this, ''));
		return $urlManager;
	}
}
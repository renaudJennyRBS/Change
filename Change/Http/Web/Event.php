<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\Event
 */
class Event extends \Change\Http\Event
{
	/**
	 * @api
	 * @return \Change\Http\Web\Controller|null
	 */
	public function getController()
	{
		if ($this->getTarget() instanceof Controller)
		{
			return $this->getTarget();
		}
		return null;
	}

	/**
	 * @api
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @api
	 * @return \Change\Presentation\Interfaces\Website|null
	 */
	public function getWebsite()
	{
		return $this->getParam('website');
	}

	/**
	 * @api
	 * @return PathRule|null
	 */
	public function getPathRule()
	{
		return $this->getParam('pathRule');
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument()
	{
		return $this->getParam('document');
	}

	/**
	 * @api
	 * @return \Change\Presentation\Interfaces\Page|null
	 */
	public function getPage()
	{
		return $this->getParam('page');
	}
}
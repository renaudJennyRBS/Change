<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Template;
use Change\Presentation\Layout\Layout;

class DefaultPageTemplate implements Template
{
	/**
	 * @var \Change\Presentation\Interfaces\Theme
	 */
	protected $theme;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \Change\Presentation\Interfaces\ThemeResource
	 */
	protected $htmlResource;

	/**
	 * @param DefaultTheme $theme
	 * @param string $name
	 */
	public function __construct($theme, $name)
	{
		$this->theme = $theme;
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * @return \Change\Presentation\Themes\DefaultTheme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	public function getCode()
	{
		return 'Change_DefaultPageTemplate';
	}

	/**
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	protected function getHtmlResource()
	{
		if ($this->htmlResource === null)
		{
			$path = 'Layout/Template/' . $this->getName() . '.twig';
			$this->htmlResource = $this->getTheme()->getAssetResource($path);
		}
		return $this->htmlResource;
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getHtml()
	{
		$res = $this->getHtmlResource();
		if (!$res->isValid())
		{
			throw new \RuntimeException('Layout/Template/' . $this->getName() . '.twig resource not found', 999999);
		}
		return $res->getContent();
	}

	/**
	 * @throws \RuntimeException
	 * @param integer $websiteId
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout($websiteId = null)
	{
		$res = $this->getTheme()->getAssetResource('Layout/Template/' . $this->getName() . '.json');
		if (!$res->isValid())
		{
			throw new \RuntimeException('Layout/Template/' . $this->getName() . '.json resource not found', 999999);
		}
		$config = json_decode($res->getContent(), true);
		return new Layout($config);
	}

	/**
	 * @return \Datetime
	 */
	public function getModificationDate()
	{
		$res = $this->getHtmlResource();
		if ($res->isValid())
		{
			return $res->getModificationDate();
		}
		return new \DateTime();
	}

	/**
	 * @return boolean
	 */
	public function isMailSuitable()
	{
		return false;
	}
}
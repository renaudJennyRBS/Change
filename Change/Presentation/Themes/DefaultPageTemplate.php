<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\PageTemplate;
use Change\Presentation\Layout\Layout;

class DefaultPageTemplate implements PageTemplate
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

	/**
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	protected function getHtmlResource()
	{
		if ($this->htmlResource === null)
		{
			$path = 'Layout/PageTemplate/' . $this->getName() . '.twig';
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
			throw new \RuntimeException('Layout/PageTemplate/' . $this->getName() . '.twig resource not found', 999999);
		}
		return $res->getContent();
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout()
	{
		$res = $this->getTheme()->getAssetResource('Layout/PageTemplate/' . $this->getName() . '.json');
		if (!$res->isValid())
		{
			throw new \RuntimeException('Layout/PageTemplate/' . $this->getName() . '.json resource not found', 999999);
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
}
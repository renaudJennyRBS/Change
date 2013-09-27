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
	 * @param DefaultTheme $theme
	 * @param string $name
	 */
	function __construct($theme, $name)
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
	 * @return \Change\Presentation\Interfaces\Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getHtml()
	{
		$res = $this->getTheme()->getResource('Layout/PageTemplate/' . $this->getName() . '.twig');
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
		$res = $this->getTheme()->getResource('Layout/PageTemplate/' . $this->getName() . '.json');
		if (!$res->isValid())
		{
			throw new \RuntimeException('Layout/PageTemplate/' . $this->getName() . '.json resource not found', 999999);
		}
		$config = json_decode($res->getContent(), true);
		return new Layout($config);
	}
}